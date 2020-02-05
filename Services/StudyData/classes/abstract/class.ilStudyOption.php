<?php
/* fau: studyData - new class ilStudyOption. */

/**
 * Base class for study options
 */
abstract class ilStudyOption
{
    /**
     * Static cache (different for all child classes)
     * @var static[]  Options indexed by id
     */
    protected static $cache;

    /**
     * Indicates if all options are already cached
     * @var bool
     */
    protected static $allCached;

    /**
     * Read options given by ids (or null if ids are empty)
     * Return array must be indexed by the id
     * This will not use the cache
     *
     * @param int[] $ids
     * @return static[]  indexed by id
     */
    abstract protected static function _read(array $ids = null): array;

    /**
     * Delete options given by ids (or null if ids are empty)
     * This will not use the cache
     *
     * @param int[] $ids
     */
    abstract protected static function _delete(array $ids = null);

    /**
     * Write the data of an option
     */
    abstract protected function write();


    /**
     * Get the id of an option
     * @return int
     */
    abstract protected function getId(): int;

    /**
     * Get the text of an option
     * @return string
     */
    abstract protected function getText(): string;


    /**
     * Get the options for a select field
     * This does not need to use a cache
     *
     * @param int $emptyId   add a 'please select' at the beginning with that id
     * @return array    id => text
     */
    public static function _getSelectOptions(int $emptyId = null): array {
        global $DIC;
        $lng = $DIC->language();

        $return = [];

        if (isset($emptyId)) {
            $return[$emptyId] = $lng->txt("please_select");
        }

        foreach (static::_getAll() as $option) {
            $return[$option->getId()] = $option->getText();
        }

        return $return;
    }

    /**
     * Get all options (with cache)
     * @return static[]
     */
    protected static function _getAll() {

        if (!static::$allCached) {
            static::$cache = static::_read();
            static::$allCached = true;
        }
        return static::$cache;
    }

    /**
     * Get an option (with cache)
     * @param int $id
     * @return static|null
     */
    public static function _get($id = null) {
        if (isset(static::$cache[$id])) {
            return static::$cache[$id];
        }

        if (isset($id)) {
            $options = static::_read([$id]);
        }

        if (!empty($options)) {
            static::$cache[$id] = $options[$id];
            return static::$cache[$id];
        }

        return null;
    }

    /**
     * Lookup the text for an option
     * This should use a static cache
     *
     * @param int|null $id
     * @return string
     */
    public static function _lookupText($id): string {
        $option = static::_get($id);

        if (isset($option)) {
            return $option->getText();
        }
        return '';
    }
}