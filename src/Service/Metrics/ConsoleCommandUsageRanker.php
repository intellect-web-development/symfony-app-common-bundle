<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;

final readonly class ConsoleCommandUsageRanker
{
    public const string CALLS_METRIC = 'console_call';

    public const string LAST_USED_METRIC = 'console_command_last_used_seconds';

    public const string COMMAND_LABEL = 'name';

    /**
     * Сначала команды без серии, затем самые старые по gauge, при равенстве — с меньшим счётчиком.
     *
     * @param array<ConsoleCommandDescriptor> $commands
     * @param array<MetricFamilySamples>      $families
     *
     * @return list<ConsoleCommandUsage>
     */
    public function rank(array $commands, array $families, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $calls = $this->valuesByCommand(families: $families, metric: self::CALLS_METRIC, type: Counter::TYPE);
        $lastUsed = $this->valuesByCommand(families: $families, metric: self::LAST_USED_METRIC, type: Gauge::TYPE);

        $usages = [];
        foreach ($commands as $command) {
            $usages[] = new ConsoleCommandUsage(
                name: $command->name,
                description: $command->description,
                callCount: $calls[$command->name] ?? null,
                lastUsedAt: $lastUsed[$command->name] ?? null,
            );
        }

        usort($usages, static function (ConsoleCommandUsage $left, ConsoleCommandUsage $right): int {
            $byNever = (int) $left->wasNeverUsed() <=> (int) $right->wasNeverUsed();
            if (0 !== $byNever) {
                return -$byNever;
            }

            $byLastUsed = ($left->lastUsedAt ?? 0) <=> ($right->lastUsedAt ?? 0);
            if (0 !== $byLastUsed) {
                return $byLastUsed;
            }

            $byCount = ($left->callCount ?? 0) <=> ($right->callCount ?? 0);
            if (0 !== $byCount) {
                return $byCount;
            }

            return $left->name <=> $right->name;
        });

        return array_slice($usages, 0, $limit);
    }

    /**
     * @param array<MetricFamilySamples> $families
     *
     * @return array<string, int>
     */
    private function valuesByCommand(array $families, string $metric, string $type): array
    {
        $values = [];
        foreach ($families as $family) {
            if (!$this->matches(family: $family, metric: $metric, type: $type)) {
                continue;
            }

            foreach ($family->getSamples() as $sample) {
                $commandName = $this->commandName(family: $family, sample: $sample);
                if (null === $commandName) {
                    continue;
                }

                $values[$commandName] = (int) round((float) $sample->getValue());
            }
        }

        return $values;
    }

    private function matches(MetricFamilySamples $family, string $metric, string $type): bool
    {
        if ($family->getType() !== $type) {
            return false;
        }

        $name = $family->getName();

        return $name === $metric || str_ends_with($name, '_' . $metric);
    }

    private function commandName(MetricFamilySamples $family, Sample $sample): ?string
    {
        $labelNames = [] !== $sample->getLabelNames() ? $sample->getLabelNames() : $family->getLabelNames();
        $labelValues = $sample->getLabelValues();
        if (count($labelNames) !== count($labelValues)) {
            return null;
        }

        $labels = array_combine($labelNames, $labelValues);
        $commandName = $labels[self::COMMAND_LABEL] ?? null;
        if (!is_string($commandName) || '' === $commandName) {
            return null;
        }

        return $commandName;
    }
}
