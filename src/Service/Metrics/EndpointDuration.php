<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

final class EndpointDuration
{
    public const NAME = 'endpoint_duration_seconds';

    public const HELP = 'Time spent in an HTTP action or MCP tool, seconds';

    public const KIND_ACTION = 'action';

    public const KIND_MCP = 'mcp';

    public const UNMATCHED = 'unmatched';

    /**
     * @var list<float>
     */
    public const BUCKETS = [0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0];

    /**
     * @var list<string>
     */
    public const LABELS = ['name', 'kind', 'status_class'];

    public static function statusClass(int $statusCode): string
    {
        $hundreds = intdiv($statusCode, 100);
        if ($hundreds < 1 || $hundreds > 5) {
            return '5xx';
        }

        return $hundreds . 'xx';
    }
}
