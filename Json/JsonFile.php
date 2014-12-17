<?php

/**
 * @file
 *
 * @package In2pire
 * @subpackage Cli
 * @author Nhat Tran <nhat.tran@inspire.vn>
 */

namespace In2pire\Cli\Json;

use JsonSchema\Validator;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * Reads/writes json files.
 */
class JsonFile
{
    const LAX_SCHEMA = 1;
    const STRICT_SCHEMA = 2;

    const JSON_UNESCAPED_SLASHES = 64;
    const JSON_PRETTY_PRINT = 128;
    const JSON_UNESCAPED_UNICODE = 256;

    private $path;
    private $rfs;

    /**
     * Initializes json file reader/parser.
     *
     * @param string $path
     *   Path to a json file.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Get file path.
     *
     * @return string
     *   Path to file.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Checks whether json file exists.
     *
     * @return boolean
     *   TRUE if file exists. Otherwise FALSE.
     */
    public function exists()
    {
        return is_file($this->path);
    }

    /**
     * Reads json file.
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function read()
    {
        try {
            $json = false;

            if ($fh = @fopen($this->path)) {
                ob_start();

                while ($chunk = fread($fh, 8192)) {
                    echo $chunk;
                }

                $json = ob_get_clean();

                fclose($fh);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not read '.$this->path."\n\n".$e->getMessage());
        }

        return static::parseJson($json, $this->path);
    }

    /**
     * Writes json file.
     *
     * @param array $hash
     *   Writes hash into json file.
     * @param int $options
     *   Json_encode options.
     *   (defaults to JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     *
     * @throws \UnexpectedValueException
     */
    public function write(array $hash, $options = 448)
    {
        $dir = dirname($this->path);

        if (!is_dir($dir)) {
            if (file_exists($dir)) {
                throw new \UnexpectedValueException(
                    $dir . ' exists and is not a directory.'
                );
            }

            if (!@mkdir($dir, 0777, true)) {
                throw new \UnexpectedValueException(
                    $dir . ' does not exist and could not be created.'
                );
            }
        }

        $retries = 3;

        while ($retries--) {
            try {
                file_put_contents($this->path, static::encode($hash, $options). ($options & self::JSON_PRETTY_PRINT ? "\n" : ''));
                break;
            } catch (\Exception $e) {
                if ($retries) {
                    usleep(500000);
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Encodes an array into (optionally pretty-printed) JSON
     *
     * @param mixed $data
     *   Data to encode into a formatted JSON string
     * @param int $options
     *   json_encode options.
     *   (defaults to JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     *
     * @return string
     *   Encoded json.
     */
    public static function encode($data, $options = 448)
    {
        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            $json = json_encode($data, $options);

            //  compact brackets to follow recent php versions
            if (PHP_VERSION_ID < 50428 || (PHP_VERSION_ID >= 50500 && PHP_VERSION_ID < 50512) || (defined('JSON_C_VERSION') && version_compare(phpversion('json'), '1.3.6', '<'))) {
                $json = preg_replace('/\[\s+\]/', '[]', $json);
                $json = preg_replace('/\{\s+\}/', '{}', $json);
            }

            return $json;
        }

        $json = json_encode($data);

        $prettyPrint = (bool) ($options & self::JSON_PRETTY_PRINT);
        $unescapeUnicode = (bool) ($options & self::JSON_UNESCAPED_UNICODE);
        $unescapeSlashes = (bool) ($options & self::JSON_UNESCAPED_SLASHES);

        if (!$prettyPrint && !$unescapeUnicode && !$unescapeSlashes) {
            return $json;
        }

        $result = JsonFormatter::format($json, $unescapeUnicode, $unescapeSlashes);

        return $result;
    }

    /**
     * Parses json string and returns hash.
     *
     * @param string $json
     *   json string.
     * @param string $file
     *   the json file.
     *
     * @return mixed
     */
    public static function parseJson($json, $file = null)
    {
        $data = json_decode($json, true);

        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            self::validateSyntax($json, $file);
        }

        return $data;
    }

    /**
     * Validates the syntax of a JSON string
     *
     * @param string $json
     *   json string.
     * @param string $file
     *   json file.
     *
     * @return bool
     *   TRUE on success.
     *
     * @throws \UnexpectedValueException
     * @throws \Seld\JsonLint\ParsingException
     */
    protected static function validateSyntax($json, $file = null)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);

        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new \UnexpectedValueException('"' . $file . '" is not UTF-8, could not parse as JSON');
            }

            return true;
        }

        throw new ParsingException('"' . $file . '" does not contain valid JSON'."\n".$result->getMessage(), $result->getDetails());
    }
}
