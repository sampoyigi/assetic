<?php

namespace Assetic\Asset;

use Assetic\Filter\FilterCollection;
use Assetic\Filter\FilterInterface;

/*
 * This file is part of the Assetic package.
 *
 * (c) Kris Wallsmith <kris.wallsmith@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A collection of assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCollection implements AssetInterface, \RecursiveIterator
{
    private $assets = array();
    private $filters;
    private $url;
    private $body;
    private $context;

    /**
     * Constructor.
     *
     * @param array $assets  Assets for the current collection
     * @param array $filters Filters for the current collection
     */
    public function __construct($assets = array(), $filters = array())
    {
        foreach ($assets as $asset) {
            $this->add($asset);
        }

        $this->filters = new FilterCollection($filters);
    }

    /**
     * Adds an asset to the current collection.
     *
     * @param AssetInterface $asset An asset
     */
    public function add(AssetInterface $asset)
    {
        $this->assets[] = $asset;
    }

    /** @inheritDoc */
    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    /** @inheritDoc */
    public function getFilters()
    {
        return $this->filters->all();
    }

    /** @inheritDoc */
    public function load(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and load each asset
        $parts = array();
        foreach (new AssetCollectionIterator($this) as $asset) {
            // snapshot
            $context = $asset->getContext();
            $asset->setContext($this->context ?: $this);

            $asset->load($additionalFilter);
            $parts[] = $asset->getBody();

            // restore
            $asset->setContext($context);
        }

        $this->body = implode("\n", $parts);
    }

    /** @inheritDoc */
    public function dump(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and dump each asset
        $parts = array();
        foreach (new AssetCollectionIterator($this) as $asset) {
            // snapshot
            $context = $asset->getContext();
            $asset->setContext($this->context ?: $this);

            $parts[] = $asset->dump($additionalFilter);

            // restore
            $asset->setContext($context);
        }

        return implode("\n", $parts);
    }

    /** @inheritDoc */
    public function getUrl()
    {
        return $this->url;
    }

    /** @inheritDoc */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /** @inheritDoc */
    public function getBody()
    {
        return $this->body;
    }

    /** @inheritDoc */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /** @inheritDoc */
    public function getContext()
    {
        return $this->context;
    }

    /** @inheritDoc */
    public function setContext(AssetInterface $context = null)
    {
        $this->context = $context;
    }

    /**
     * Returns the content type of the first asset in the current collection.
     *
     * @return string|null The content type
     */
    public function getContentType()
    {
        if (isset($this->assets[0])) {
            return $this->assets[0]->getContentType();
        }
    }

    /**
     * Returns the highest last-modified value of all assets in the current collection.
     *
     * @return integer|null A UNIX timestamp
     */
    public function getLastModified()
    {
        $lastModified = null;
        foreach ($this->assets as $asset) {
            $mtime = $asset->getLastModified();
            if ($lastModified < $mtime) {
                $lastModified = $mtime;
            }
        }

        return $lastModified;
    }

    /** @inheritDoc */
    public function current()
    {
        $asset = clone current($this->assets);
        $asset->ensureFilter($this->filters);

        return $asset;
    }

    /** @inheritDoc */
    public function key()
    {
        return key($this->assets);
    }

    /** @inheritDoc */
    public function next()
    {
        return next($this->assets);
    }

    /** @inheritDoc */
    public function rewind()
    {
        return reset($this->assets);
    }

    /** @inheritDoc */
    public function valid()
    {
        return false !== current($this->assets);
    }

    /** @inheritDoc */
    public function getChildren()
    {
        $asset = current($this->assets);
        if ($asset instanceof self) {
            return $asset;
        } else {
            return new self();
        }
    }

    /** @inheritDoc */
    public function hasChildren()
    {
        return current($this->assets) instanceof self;
    }
}
