<?php
/**
 * Description of DbalLogger.php.
 *
 * @package Kinone\Bundle\Bridge\Doctrine
 * @author Kinone\Bundle\Bridge;
 */
namespace Kinone\Bundle\Bridge\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

class DbalLogger implements SQLLogger
{
    const MAX_STRING_LENGTH = 32;
    const BINARY_DATA_VALUE = '(binary value)';
    
    private $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types The SQL parameter types.
     *
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->log($sql, null == $params ? [] : $this->normalizeParams($params));
    }

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery()
    {
        // TODO: Implement stopQuery() method.
    }

    /**
     * Logs a message.
     *
     * @param string $message A message to log
     * @param array  $params  The context
     */
    protected function log($message, array $params)
    {
        $this->logger->debug($message, $params);
    }

    private function normalizeParams(array $params)
    {
        foreach ($params as $index => $param) {
            // normalize recursively
            if (is_array($param)) {
                $params[$index] = $this->normalizeParams($param);
                continue;
            }

            if (!is_string($params[$index])) {
                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $params[$index])) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            // detect if the too long string must be shorten
            if (self::MAX_STRING_LENGTH < iconv_strlen($params[$index], 'UTF-8')) {
                $params[$index] = iconv_substr($params[$index], 0, self::MAX_STRING_LENGTH - 6, 'UTF-8').' [...]';
                continue;
            }
        }

        return $params;
    }
}