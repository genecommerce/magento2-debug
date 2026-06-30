<?php
declare(strict_types=1);

namespace ClawRock\Debug\Model\Database;

class TraceProfiler extends \Magento\Framework\DB\Profiler
{
    private array $queryTraces = [];

    public function queryStart($queryText, $queryType = null)
    {
        $trace = $this->getBacktrace();
        $queryId = parent::queryStart($queryText, $queryType);

        if ($queryId === null || $queryId === self::IGNORED) {
            return $queryId;
        }

        $this->queryTraces[$queryId] = $trace;

        return $queryId;
    }

    public function getQueryTrace($queryId): array
    {
        return $this->queryTraces[$queryId] ?? [];
    }

    private function getBacktrace(): array
    {
        if (!function_exists('debug_backtrace')) {
            return [];
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        while ($backtrace && $this->isInternalTraceCall(reset($backtrace))) {
            array_shift($backtrace);
        }

        return array_map(function (array $item): array {
            unset($item['object'], $item['args'], $item['type']);

            return $item;
        }, $backtrace);
    }

    private function isInternalTraceCall(array $call): bool
    {
        if (!isset($call['class'])) {
            return false;
        }

        return in_array($call['class'], [
            self::class,
            \Magento\Framework\DB\Profiler::class,
            \Zend_Db_Profiler::class,
        ], true);
    }
}
