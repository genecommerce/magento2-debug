<?php
declare(strict_types=1);

namespace ClawRock\Debug\Model\Info;

class DatabaseInfo
{
    public const PROFILE = 'profile';
    public const ALL_QUERIES = 'all';
    public const DUPLICATED_QUERIES = 'duplicated';
    public const QUERY_TRACES = 'traces';

    private ?\Zend_Db_Profiler $profiler = null;

    public function __construct(
        private \Magento\Framework\App\ResourceConnection $resourceConnection,
        private \ClawRock\Debug\Helper\Database $databaseHelper
    ) {
    }

    public function getQueries(): array
    {
        $queries = [
            self::ALL_QUERIES => [],
            \Zend_Db_Profiler::SELECT => [],
            \Zend_Db_Profiler::INSERT => [],
            \Zend_Db_Profiler::UPDATE => [],
            \Zend_Db_Profiler::DELETE => [],
            \Zend_Db_Profiler::QUERY => [],
            self::QUERY_TRACES => [],
        ];

        $profiler = $this->getProfiler();
        $queryProfiles = $profiler->getQueryProfiles();
        if ($queryProfiles === false) {
            return $queries;
        }

        /** @var \Zend_Db_Profiler_Query $query */
        foreach ($queryProfiles as $queryId => $query) {
            $type = $query->getQueryType();
            if (!isset($queries[$type])) {
                $type = \Zend_Db_Profiler::QUERY;
            }
            $queries[$type][$queryId] = $query;

            $queries[self::ALL_QUERIES][$queryId] = $query;
            if (method_exists($profiler, 'getQueryTrace')) {
                $queries[self::QUERY_TRACES][$queryId] = $profiler->getQueryTrace($queryId);
            }
        }

        $queries[self::DUPLICATED_QUERIES] = $this->databaseHelper->getDuplicatedQueries();

        return $queries;
    }

    public function getTotalTime(): float
    {
        return $this->getProfiler()->getTotalElapsedSecs();
    }

    public function getQueriesCount(): int
    {
        return $this->getProfiler()->getTotalNumQueries();
    }

    private function getProfiler(): \Zend_Db_Profiler
    {
        if ($this->profiler === null) {
            /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
            $connection = $this->resourceConnection->getConnection();
            $this->profiler = $connection->getProfiler();
        }

        return $this->profiler;
    }
}
