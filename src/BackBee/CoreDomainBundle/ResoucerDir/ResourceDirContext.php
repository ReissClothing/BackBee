<?php
/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 * @date 04/11/15
 * @copyright Copyright (c) Reiss Clothing Ltd.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use BackBee\CoreDomain\Exception\BBException;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class ResourceDirContext
{
    private $resourceDir;
    private $baseRepositoryDir;
    private $repositoryDir;

    public function __construct($baseRepositoryDir)
    {
        $this->baseRepositoryDir = $baseRepositoryDir;
    }
    /**
     * Return the resource directories, if undefined, initialized with common resources.
     *
     * @return array The resource directories
     */
    public function getResourceDir()
    {
        if (null === $this->resourceDir) {
            $this->initResourceDir();
        }

        return $this->resourceDir;
    }

    /**
     * Prepend one directory of resources.
     *
     * @param String $dir The new resource directory to add
     *
     * @return ApplicationInterface The current BBApplication
     *
     * @throws BBException Occur on invalid path or invalid resource directories
     */
    public function addResourceDir($dir)
    {
        if (null === $this->resourceDir) {
            $this->initResourceDir();
        }

        if (!is_array($this->resourceDir)) {
            throw new BBException(
                'Misconfiguration of the BBApplication : resource dir has to be an array',
                BBException::INVALID_ARGUMENT
            );
        }

        if (!file_exists($dir) || !is_dir($dir)) {
            throw new BBException(
                sprintf('The resource folder `%s` does not exist or is not a directory', $dir),
                BBException::INVALID_ARGUMENT
            );
        }

        array_unshift($this->resourceDir, $dir);

        return $this;
    }

    /**
     * Init the default resource directories
     */
    protected function initResourceDir()
    {
        $this->resourceDir = [];

        $this->addResourceDir($this->getBBDir() . '/Resources');

        if (is_dir($this->baseRepositoryDir . '/Resources')) {
            $this->addResourceDir($this->getBaseRepository() . '/Resources');
        }

        if (is_dir($this->baseRepositoryDir . '/Ressources')) {
            $this->addResourceDir($this->getBaseRepository() . '/Ressources');
        }

// @TODO gonzalo
//        if ($this->hasContext()) {
//            if (is_dir($this->getRepository() . '/Resources')) {
//                $this->addResourceDir($this->getRepository() . '/Resources');
//            }
//
//            if (is_dir($this->getRepository() . '/Resources')) {
//                $this->addResourceDir($this->getRepository() . '/Resources');
//            }
//        }

        array_map(['BackBee\Utils\File\File', 'resolveFilepath'], $this->resourceDir);
    }

    protected function getRepository()
    {
        if (null === $this->repositoryDir) {
            $this->$repositoryDir = $this->baseRepositoryDir;
            // @TODO gonzalo
//            if ($this->hasContext()) {
//                $this->$repositoryDir .= DIRECTORY_SEPARATOR.$this->context;
//            }
        }

        return $this->$repositoryDir;
    }
}