#!/usr/bin/env python3
"""Migrate one IWD monolith onto intellect-web-development/symfony-app-common-bundle ^1.0."""
from __future__ import annotations

import json
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
VCS_URL = "https://github.com/intellect-web-development/symfony-app-common-bundle.git"
PKG_NAME = "intellect-web-development/symfony-app-common-bundle"
PKG_CONSTRAINT = "^1.0"

RESIDUAL_PREFIXES = (
    "Messages/",
    "Accounting/",
    "Payment/",
    "Terms/",
    "Filesystem/",
    "Profile/",
    "Math/",
    "Infrastructure/Doctrine/Filesystem/",
    "Service/Dispatcher/",
    "Entry/Console/InitTermsDocuments/",
    "Entry/MessageBus/",
    "Entry/Console/SymfonyEnvEncryptor/",
)
RESIDUAL_FILES = {
    "Service/Core/AggregateRoot.php",
    "Service/Core/EventDispatcher.php",
    "Service/Core/EventsTrait.php",
    "Service/Core/PreFlushEventHandler.php",
    "Service/Core/PostFlushEventHandler.php",
    "Service/Core/PreFlushEvent.php",
    "Service/Core/PostFlushEvent.php",
    "Domain/Attribute/FilesystemFileId.php",
    "Domain/Attribute/StoredFile.php",
}
RESIDUAL_NAME_RE = re.compile(r"UnavailableHttpException\.php$")

PACKAGE_USE_REWRITES = [
    ("use App\\Common\\Service\\Core\\Flusher;", "use IWD\\SymfonyAppCommon\\Service\\Core\\Flusher;"),
    (
        "use App\\Common\\Service\\Core\\TransactionSideEffectInterface;",
        "use IWD\\SymfonyAppCommon\\Service\\Core\\TransactionSideEffectInterface;",
    ),
    ("use App\\Common\\Domain\\Attribute\\UpdatedAt;", "use IWD\\SymfonyAppCommon\\Domain\\Attribute\\UpdatedAt;"),
    ("use App\\Common\\Exception\\Domain\\DomainException;", "use IWD\\SymfonyAppCommon\\Exception\\Domain\\DomainException;"),
    (
        "use App\\Common\\Exception\\Http\\AccessDeniedHttpException;",
        "use IWD\\SymfonyAppCommon\\Exception\\Http\\AccessDeniedHttpException;",
    ),
    (
        "use App\\Common\\Exception\\Http\\ToManyRequestException;",
        "use IWD\\SymfonyAppCommon\\Exception\\Http\\ToManyRequestException;",
    ),
    ("use App\\Common\\Dictionary\\EnvDictionary;", "use IWD\\SymfonyAppCommon\\Dictionary\\EnvDictionary;"),
    ("use App\\Common\\Service\\Metrics\\", "use IWD\\SymfonyAppCommon\\Service\\Metrics\\"),
    ("use App\\Common\\Service\\Cipher\\", "use IWD\\SymfonyAppCommon\\Service\\Cipher\\"),
    ("use App\\Common\\Service\\Json\\", "use IWD\\SymfonyAppCommon\\Service\\Json\\"),
    ("use App\\Common\\Service\\Lock\\", "use IWD\\SymfonyAppCommon\\Service\\Lock\\"),
    ("use App\\Common\\Service\\Http\\", "use IWD\\SymfonyAppCommon\\Service\\Http\\"),
    ("use App\\Common\\Service\\Redis\\", "use IWD\\SymfonyAppCommon\\Service\\Redis\\"),
    ("use App\\Common\\Service\\Notifier\\", "use IWD\\SymfonyAppCommon\\Service\\Notifier\\"),
    ("use App\\Common\\Service\\Security\\", "use IWD\\SymfonyAppCommon\\Service\\Security\\"),
    ("use App\\Common\\Container\\", "use IWD\\SymfonyAppCommon\\Container\\"),
    ("use App\\Common\\Application\\", "use IWD\\SymfonyAppCommon\\Application\\"),
    ("use App\\Common\\Validator\\", "use IWD\\SymfonyAppCommon\\Validator\\"),
    ("use App\\Common\\Doctrine\\", "use IWD\\SymfonyAppCommon\\Doctrine\\"),
    (
        "use App\\Common\\Infrastructure\\Doctrine\\QueryBuilderParametersCopier;",
        "use IWD\\SymfonyAppCommon\\Infrastructure\\Doctrine\\QueryBuilderParametersCopier;",
    ),
    (
        "use App\\Common\\Infrastructure\\Doctrine\\Timestamp\\",
        "use IWD\\SymfonyAppCommon\\Infrastructure\\Doctrine\\Timestamp\\",
    ),
    ("use App\\Common\\EventSubscriber\\", "use IWD\\SymfonyAppCommon\\EventSubscriber\\"),
    ("use App\\Common\\Entry\\Console\\", "use IWD\\SymfonyAppCommon\\Entry\\Console\\"),
    ("use App\\Common\\RateLimiter\\", "use IWD\\SymfonyAppCommon\\RateLimiter\\"),
    ("use App\\Common\\Security\\PlatformToken\\", "use IWD\\SymfonyAppCommon\\Security\\PlatformToken\\"),
    (
        "use App\\Common\\Exception\\LogExceptionFormatter;",
        "use IWD\\SymfonyAppCommon\\Exception\\LogExceptionFormatter;",
    ),
    (
        "use App\\Common\\Exception\\WithContextExceptionInterface;",
        "use IWD\\SymfonyAppCommon\\Exception\\WithContextExceptionInterface;",
    ),
]


def is_residual(rel: str) -> bool:
    if rel in RESIDUAL_FILES:
        return True
    if any(rel.startswith(p) for p in RESIDUAL_PREFIXES):
        return True
    if RESIDUAL_NAME_RE.search(rel):
        return True
    if rel.startswith("Service/Core/") and rel.endswith(".php"):
        name = Path(rel).name
        if name in {"AggregateRoot.php", "EventDispatcher.php", "EventsTrait.php"} or "Flush" in name:
            return True
    return False


def keep_product_logtag(common: Path) -> bool:
    p = common / "Dictionary" / "LogTag.php"
    if not p.exists():
        return False
    text = p.read_text(encoding="utf-8")
    return bool(re.search(r"PAYMENT|RESALE|RECIPE|BOND|ACCOUNT|SPREADER|FILESYSTEM", text))


def patch_composer(mono: Path) -> None:
    path = mono / "composer.json"
    data = json.loads(path.read_text(encoding="utf-8"))
    repos = data.setdefault("repositories", [])
    # drop leftover path repos to local checkout
    repos = [
        r
        for r in repos
        if not (
            isinstance(r, dict)
            and "symfony-app-common-bundle" in str(r.get("url", ""))
            and r.get("type") == "path"
        )
    ]
    if not any(
        isinstance(r, dict)
        and r.get("type") == "vcs"
        and "symfony-app-common-bundle" in str(r.get("url", ""))
        for r in repos
    ):
        repos.insert(0, {"type": "vcs", "url": VCS_URL})
    data["repositories"] = repos
    data.setdefault("require", {})[PKG_NAME] = PKG_CONSTRAINT
    path.write_text(json.dumps(data, indent=4, ensure_ascii=False) + "\n", encoding="utf-8")


def patch_bundles(mono: Path) -> None:
    path = mono / "config" / "bundles.php"
    if not path.exists():
        return
    text = path.read_text(encoding="utf-8")
    if "SymfonyAppCommonBundle" in text:
        return
    line = "    IWD\\SymfonyAppCommon\\SymfonyAppCommonBundle::class => ['all' => true],"
    needle = "IWD\\SymfonyDoctrineSearch\\SymfonyDoctrineSearchBundle::class => ['all' => true],"
    if needle in text:
        text = text.replace(needle, needle + "\n" + line)
    else:
        text = text.replace("return [", "return [\n" + line, 1)
    path.write_text(text, encoding="utf-8")


def codemod_tree(mono: Path) -> None:
    for base in (mono / "src", mono / "tests", mono / "config"):
        if not base.exists():
            continue
        for f in base.rglob("*"):
            if f.suffix not in {".php", ".yaml", ".yml"}:
                continue
            text = f.read_text(encoding="utf-8")
            new = text.replace("App\\Common", "IWD\\SymfonyAppCommon")
            if new != text:
                f.write_text(new, encoding="utf-8")


def restore_residuals(common: Path, residuals: list[str]) -> None:
    for rel in residuals:
        f = common / rel
        if not f.exists():
            continue
        text = f.read_text(encoding="utf-8")
        text = text.replace("IWD\\SymfonyAppCommon", "App\\Common")
        for old, new in PACKAGE_USE_REWRITES:
            text = text.replace(old, new)
        # product LogTag must stay App\Common — do not rewrite LogTag use back to package
        f.write_text(text, encoding="utf-8")


def restore_product_logtag_usages(mono: Path) -> None:
    """If app keeps App\\Common\\Dictionary\\LogTag, point imports back to it."""
    common = mono / "src" / "Common"
    if not keep_product_logtag(common):
        return
    # ensure LogTag file itself is App\Common
    logtag = common / "Dictionary" / "LogTag.php"
    if logtag.exists():
        t = logtag.read_text(encoding="utf-8").replace("IWD\\SymfonyAppCommon", "App\\Common")
        logtag.write_text(t, encoding="utf-8")
    old = "use IWD\\SymfonyAppCommon\\Dictionary\\LogTag;"
    new = "use App\\Common\\Dictionary\\LogTag;"
    for base in (mono / "src", mono / "tests"):
        if not base.exists():
            continue
        for f in base.rglob("*.php"):
            text = f.read_text(encoding="utf-8")
            if old in text:
                f.write_text(text.replace(old, new), encoding="utf-8")


def patch_filesystem_cleanup(common: Path) -> None:
    f = common / "Infrastructure/Doctrine/Filesystem/FilesystemFileCleanup.php"
    if not f.exists():
        return
    text = f.read_text(encoding="utf-8")
    if "TransactionSideEffectInterface" in text:
        return
    text = text.replace(
        "namespace App\\Common\\Infrastructure\\Doctrine\\Filesystem;\n",
        "namespace App\\Common\\Infrastructure\\Doctrine\\Filesystem;\n\n"
        "use IWD\\SymfonyAppCommon\\Service\\Core\\TransactionSideEffectInterface;\n",
    )
    text = text.replace(
        "final class FilesystemFileCleanup\n",
        "final class FilesystemFileCleanup implements TransactionSideEffectInterface\n",
    )
    f.write_text(text, encoding="utf-8")


def delete_moved(common: Path, residuals: set[str]) -> None:
    for f in list(common.rglob("*")):
        if not f.is_file():
            continue
        rel = str(f.relative_to(common)).replace("\\", "/")
        if rel.startswith("Resource/"):
            continue
        if rel in residuals or is_residual(rel):
            continue
        if rel == "Dictionary/LogTag.php" and keep_product_logtag(common):
            continue
        f.unlink(missing_ok=True)
    for d in sorted(common.rglob("*"), reverse=True):
        if d.is_dir():
            try:
                d.rmdir()
            except OSError:
                pass


def slim_resource(common: Path) -> None:
    resource = common / "Resource"
    if not resource.exists():
        return
    for name in (
        "config/services/doctrine_types.yaml",
        "config/services/doctrine_mappings.yaml",
        "config/services/translation.yaml",
    ):
        (resource / name).unlink(missing_ok=True)
    trans = resource / "translations"
    if trans.exists():
        shutil.rmtree(trans, ignore_errors=True)

    left_php = [p for p in common.rglob("*.php") if "Resource" not in p.parts]
    cfg = resource / "config"
    cfg.mkdir(parents=True, exist_ok=True)
    services = cfg / "services.yaml"
    if left_php:
        services.write_text(
            "services:\n"
            "    _defaults:\n"
            "        autowire: true\n"
            "        autoconfigure: true\n\n"
            "    App\\Common\\:\n"
            "        resource: '%kernel.project_dir%/src/Common/'\n"
            "        exclude:\n"
            "            - '../Resource'\n",
            encoding="utf-8",
        )
    else:
        services.unlink(missing_ok=True)
        (cfg / "services_test.yaml").unlink(missing_ok=True)
        svc_dir = cfg / "services"
        if svc_dir.exists():
            for p in svc_dir.glob("*.yaml"):
                if p.name == "messenger.yaml":
                    continue
                p.unlink(missing_ok=True)


def patch_kernel(mono: Path) -> None:
    kernel = mono / "src" / "Kernel.php"
    if not kernel.exists():
        return
    text = kernel.read_text(encoding="utf-8")
    text = re.sub(r"use App\\Common\\Container\\CompillerPass\\RateLimitingPass;\n", "", text)
    text = re.sub(r"use IWD\\SymfonyAppCommon\\Container\\CompillerPass\\RateLimitingPass;\n", "", text)
    text = re.sub(
        r"\n    protected function build\(ContainerBuilder \$container\): void\n    \{\n"
        r"        \$container->addCompilerPass\(new RateLimitingPass\(\)\);\n    \}\n",
        "\n",
        text,
    )
    if "addCompilerPass" not in text and "ContainerBuilder" not in text.replace(
        "use Symfony\\Component\\DependencyInjection\\ContainerBuilder;\n", ""
    ):
        pass
    if "addCompilerPass" not in text:
        text = re.sub(r"use Symfony\\Component\\DependencyInjection\\ContainerBuilder;\n", "", text)
    kernel.write_text(text, encoding="utf-8")


def patch_migrations(mono: Path) -> None:
    for name in ("doctrine_migrations.yaml", "doctrine_migrations.yml"):
        path = mono / "config" / "packages" / name
        if not path.exists():
            continue
        text = path.read_text(encoding="utf-8")
        repl = (
            "%kernel.project_dir%/vendor/intellect-web-development/symfony-app-common-bundle"
            "/src/Doctrine/Template/MigrationTemplate.dist"
        )
        text = text.replace("./src/Common/Doctrine/Template/MigrationTemplate.dist", repl)
        text = text.replace(
            "%kernel.project_dir%/src/Common/Doctrine/Template/MigrationTemplate.dist",
            repl,
        )
        path.write_text(text, encoding="utf-8")


def patch_module(mono: Path, common: Path) -> None:
    modules_dir = mono / "src" / ".modules"
    if not modules_dir.exists():
        return
    modules = list(modules_dir.glob("*ommon.module.php"))
    has_cfg = (
        any((common / "Resource" / "config").rglob("*.yaml"))
        if (common / "Resource" / "config").exists()
        else False
    )
    left_php = [p for p in common.rglob("*.php") if "Resource" not in p.parts] if common.exists() else []
    if not has_cfg and not left_php:
        for m in modules:
            m.unlink(missing_ok=True)


def migrate(repo: str) -> None:
    mono = ROOT / repo / "backend" / "monolith"
    common = mono / "src" / "Common"
    if not common.exists():
        print(f"SKIP {repo}: no Common")
        return
    print(f"==> {repo}")
    patch_composer(mono)
    patch_bundles(mono)
    codemod_tree(mono)

    residuals: list[str] = []
    for f in common.rglob("*"):
        if not f.is_file():
            continue
        rel = str(f.relative_to(common)).replace("\\", "/")
        if is_residual(rel) or (rel == "Dictionary/LogTag.php" and keep_product_logtag(common)):
            residuals.append(rel)

    restore_residuals(common, residuals)

    flusher = common / "Service/Core/Flusher.php"
    if flusher.exists() and "FilesystemFileCleanup" in flusher.read_text(encoding="utf-8"):
        flusher.unlink()
        residuals = [r for r in residuals if r != "Service/Core/Flusher.php"]

    patch_filesystem_cleanup(common)
    delete_moved(common, set(residuals))
    slim_resource(common)
    restore_product_logtag_usages(mono)
    patch_kernel(mono)
    patch_migrations(mono)
    patch_module(mono, common)
    left = list(common.rglob("*.php")) if common.exists() else []
    print(f"done {repo}; residual php={len(left)}")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("usage: migrate_app_common.py <repo> [repo...]", file=sys.stderr)
        sys.exit(2)
    for repo in sys.argv[1:]:
        migrate(repo)
