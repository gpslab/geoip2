<?php
declare(strict_types=1);

/**
 * GpsLab component.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2017, Peter Gribanov
 * @license   http://opensource.org/licenses/MIT
 */

namespace GpsLab\Bundle\GeoIP2Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const URL = 'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz';

    private const PATH = '%s/%s.mmdb';

    /**
     * @var string
     */
    private $cache_dir;

    /**
     * @param string|null $cache_dir
     */
    public function __construct(?string $cache_dir)
    {
        $this->cache_dir = $cache_dir ?: sys_get_temp_dir();
    }

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree_builder = $this->createTreeBuilder('gpslab_geoip');
        $root_node = $this->getRootNode($tree_builder, 'gpslab_geoip');

        $this->normalizeDefaultDatabase($root_node);
        $this->normalizeRootConfigurationToDefaultDatabase($root_node);
        $this->validateAvailableDefaultDatabase($root_node);
        $this->allowGlobalLicense($root_node);
        $this->allowGlobalLocales($root_node);
        $this->validateDatabaseLocales($root_node);

        $root_node->fixXmlConfig('locale');
        $locales = $root_node->children()->arrayNode('locales');
        $locales->prototype('scalar');
        $locales
            ->treatNullLike([])
            ->defaultValue(['en']);

        $root_node->children()->scalarNode('license');

        $default_database = $root_node->children()->scalarNode('default_database');
        $default_database->defaultValue('default');

        $root_node->fixXmlConfig('database');
        $root_node->append($this->getDatabaseNode());

        return $tree_builder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function getDatabaseNode(): ArrayNodeDefinition
    {
        $tree_builder = $this->createTreeBuilder('databases');
        $root_node = $this->getRootNode($tree_builder, 'databases');
        $root_node->useAttributeAsKey('name');

        $database_node = $this->arrayPrototype($root_node);

        $this->normalizeUrl($database_node);
        $this->normalizePath($database_node);

        $url = $database_node->children()->scalarNode('url');
        $url->isRequired();

        $this->validateURL($url);

        $path = $database_node->children()->scalarNode('path');
        $path->isRequired();

        $database_node->fixXmlConfig('locale');
        $locales = $database_node->children()->arrayNode('locales');
        $locales->prototype('scalar');
        $locales
            ->treatNullLike([])
            ->requiresAtLeastOneElement()
            ->defaultValue(['en']);

        $database_node->children()->scalarNode('license');

        $database_node->children()->scalarNode('edition');

        return $root_node;
    }

    /**
     * @param string $name
     *
     * @return TreeBuilder
     */
    private function createTreeBuilder(string $name): TreeBuilder
    {
        // Symfony 4.2 +
        if (method_exists(TreeBuilder::class, '__construct')) {
            return new TreeBuilder($name);
        }

        // Symfony 4.1 and below
        return new TreeBuilder();
    }

    /**
     * @param TreeBuilder $tree_builder
     * @param string      $name
     *
     * @return ArrayNodeDefinition
     */
    private function getRootNode(TreeBuilder $tree_builder, string $name): ArrayNodeDefinition
    {
        if (method_exists($tree_builder, 'getRootNode')) {
            // Symfony 4.2 +
            $root = $tree_builder->getRootNode();
        } else {
            // Symfony 4.1 and below
            $root = $tree_builder->root($name);
        }

        // @codeCoverageIgnoreStart
        if (!($root instanceof ArrayNodeDefinition)) { // should be always false
            throw new \RuntimeException(sprintf('The root node should be instance of %s, got %s instead.', ArrayNodeDefinition::class, get_class($root)));
        }
        // @codeCoverageIgnoreEnd

        return $root;
    }

    /**
     * @param ArrayNodeDefinition $root_node
     *
     * @return ArrayNodeDefinition
     */
    private function arrayPrototype(ArrayNodeDefinition $root_node): ArrayNodeDefinition
    {
        // Symfony 3.3 +
        if (method_exists($root_node, 'arrayPrototype')) {
            return $root_node->arrayPrototype();
        }

        // Symfony 3.2 and below
        $node = $root_node->prototype('array');

        // @codeCoverageIgnoreStart
        if (!($node instanceof ArrayNodeDefinition)) { // should be always false
            throw new \RuntimeException(sprintf('The "array" prototype should be instance of %s, got %s instead.', ArrayNodeDefinition::class, get_class($node)));
        }
        // @codeCoverageIgnoreEnd

        return $node;
    }

    /**
     * Normalize default_database from databases.
     *
     * @param NodeDefinition $root_node
     */
    private function normalizeDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return
                    is_array($v) &&
                    !array_key_exists('default_database', $v) &&
                    array_key_exists('databases', $v) &&
                    is_array($v['databases']) &&
                    $v['databases'];
            })
            ->then(static function (array $v): array {
                $keys = array_keys($v['databases']);
                $v['default_database'] = reset($keys);

                return $v;
            });
    }

    /**
     * Normalize databases root configuration to default_database.
     *
     * @param NodeDefinition $root_node
     */
    private function normalizeRootConfigurationToDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return $v && is_array($v) && !array_key_exists('databases', $v) && !array_key_exists('database', $v);
            })
            ->then(static function (array $v): array {
                // key that should not be rewritten to the database config
                $database = [];
                foreach ($v as $key => $value) {
                    if ($key !== 'default_database') {
                        $database[$key] = $v[$key];
                        unset($v[$key]);
                    }
                }
                $v['default_database'] = isset($v['default_database']) ? (string) $v['default_database'] : 'default';
                $v['databases'] = [$v['default_database'] => $database];

                return $v;
            });
    }

    /**
     * Validate that the default_database exists in the list of databases.
     *
     * @param NodeDefinition $root_node
     */
    private function validateAvailableDefaultDatabase(NodeDefinition $root_node): void
    {
        $root_node
            ->validate()
                ->ifTrue(static function ($v): bool {
                    return
                        is_array($v) &&
                        array_key_exists('default_database', $v) &&
                        array_key_exists('databases', $v) &&
                        $v['databases'] &&
                        !array_key_exists($v['default_database'], $v['databases']);
                })
                ->then(static function (array $v): array {
                    $databases = implode('", "', array_keys($v['databases']));

                    throw new \InvalidArgumentException(sprintf('Undefined default database "%s". Available "%s" databases.', $v['default_database'], $databases));
                });
    }

    /**
     * Add a license option to the databases configuration if it does not exist.
     * Allow use a global license for all databases.
     *
     * @param NodeDefinition $root_node
     */
    private function allowGlobalLicense(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return
                    is_array($v) &&
                    array_key_exists('license', $v) &&
                    array_key_exists('databases', $v) &&
                    is_array($v['databases']);
            })
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (!array_key_exists('license', $database)) {
                        $v['databases'][$name]['license'] = $v['license'];
                    }
                }

                return $v;
            });
    }

    /**
     * Add a locales option to the databases configuration if it does not exist.
     * Allow use a global locales for all databases.
     *
     * @param NodeDefinition $root_node
     */
    private function allowGlobalLocales(NodeDefinition $root_node): void
    {
        $root_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return
                    is_array($v) &&
                    array_key_exists('locales', $v) &&
                    array_key_exists('databases', $v) &&
                    is_array($v['databases']);
            })
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (!array_key_exists('locales', $database)) {
                        $v['databases'][$name]['locales'] = $v['locales'];
                    }
                }

                return $v;
            });
    }

    /**
     * Validate database locales.
     *
     * @param NodeDefinition $root_node
     */
    private function validateDatabaseLocales(NodeDefinition $root_node): void
    {
        $root_node
            ->validate()
            ->ifTrue(static function ($v): bool {
                return is_array($v) && array_key_exists('databases', $v) && is_array($v['databases']);
            })
            ->then(static function (array $v): array {
                foreach ($v['databases'] as $name => $database) {
                    if (empty($database['locales'])) {
                        throw new \InvalidArgumentException(sprintf('The list of locales should not be empty in databases "%s".', $name));
                    }
                }

                return $v;
            });
    }

    /**
     * Normalize url option from license key and edition id.
     *
     * @param NodeDefinition $database_node
     */
    private function normalizeUrl(NodeDefinition $database_node): void
    {
        $database_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return
                    is_array($v) &&
                    !array_key_exists('url', $v) &&
                    array_key_exists('license', $v) &&
                    array_key_exists('edition', $v);
            })
            ->then(static function (array $v): array {
                $v['url'] = sprintf(self::URL, urlencode($v['edition']), urlencode($v['license']));

                return $v;
            });
    }

    /**
     * Normalize path option from edition id.
     *
     * @param NodeDefinition $database_node
     */
    private function normalizePath(NodeDefinition $database_node): void
    {
        $database_node
            ->beforeNormalization()
            ->ifTrue(static function ($v): bool {
                return is_array($v) && !array_key_exists('path', $v) && array_key_exists('edition', $v);
            })
            ->then(function (array $v): array {
                $v['path'] = sprintf(self::PATH, $this->cache_dir, $v['edition']);

                return $v;
            });
    }

    /**
     * The url option must be a valid URL.
     *
     * @param NodeDefinition $url
     */
    private function validateURL(NodeDefinition $url): void
    {
        $url
            ->validate()
            ->ifTrue(static function ($v): bool {
                return is_string($v) && !filter_var($v, FILTER_VALIDATE_URL);
            })
            ->then(static function (string $v): array {
                throw new \InvalidArgumentException(sprintf('URL "%s" must be valid.', $v));
            });
    }
}
