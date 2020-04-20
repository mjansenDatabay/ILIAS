<?php
require_once('./Services/GlobalCache/classes/class.ilGlobalCache.php');

/**
 * Class ilCachedLanguage
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class ilCachedLanguage
{
    protected $global_cache;
    /**
     * @var bool
     */
    protected $loaded = false;
    /**
     * @var string
     */
    protected $language_key = 'en';
    /**
     * @var array
     */
    protected $translations = array();
    /**
     * @var ilCachedLanguage[]
     */
    protected static $instances = array();


    /**
     * @param $language_key
     */
    protected function __construct($language_key)
    {
        $this->setLanguageKey($language_key);
        /**
         * @var $ilUser         ilObjUser
         * @var $ilLog          ilLog
         */
        $this->global_cache = ilGlobalCache::getInstance(ilGlobalCache::COMP_CLNG);
        $this->readFromCache();
        if (!$this->getLoaded()) {
            $this->readFromDB();
            $this->writeToCache();
            $this->setLoaded(true);
        }
    }


    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->global_cache->isActive();
    }


    protected function readFromCache()
    {
        // fau: cacheByCode - try to read from saved code
        if (ilCust::get('cache_by_code')) {
            log_line('read_from_code');
            $translations = $this->readFromCode();
            if (is_array($translations)) {
                $this->setTranslations($translations);
                $this->setLoaded(true);
                return;
            }
        }
        // fau.

        if ($this->global_cache->isActive()) {
            $translations = $this->global_cache->get('translations_' . $this->getLanguageKey());
            if (is_array($translations)) {
                $this->setTranslations($translations);
                $this->setLoaded(true);
            }
        }
    }


    public function writeToCache()
    {
        if ($this->global_cache->isActive()) {
            $this->global_cache->set('translations_' . $this->getLanguageKey(), $this->getTranslations());
        }
    }

    // fau: cacheByCode - new function readFromCode()
    protected function readFromCode() {
        $variable = null;
        try {
            include(CLIENT_DATA_DIR . '/ilGlobalCache/translations_'. $this->getLanguageKey().'.php');
        }
        catch (Exception $e) {
        }

        return $variable;
    }
    // fau.

    // fau: cacheByCode - new function writeToCode()
    public function writeToCode()
    {
        if (!is_dir(CLIENT_DATA_DIR . '/ilGlobalCache')) {
            ilUtil::makeDir(CLIENT_DATA_DIR . '/ilGlobalCache');
        }

        $code = '<?php $variable = '. var_export($this->getTranslations(), true) .';';
        file_put_contents(CLIENT_DATA_DIR . '/ilGlobalCache/translations_'. $this->getLanguageKey().'.php', $code);
    }
    // fau.

    protected function readFromDB()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $q = 'SELECT module, lang_array FROM lng_modules WHERE lang_key = %s';
        $res = $ilDB->queryF($q, array( 'text' ), array( $this->getLanguageKey() ));
        $translations = array();
        while ($set = $ilDB->fetchObject($res)) {
            $lang_array = unserialize($set->lang_array);
            if (is_array($lang_array)) {
                $translations[$set->module] = $lang_array;
            }
        }
        $this->setTranslations($translations);
    }


    /**
     * @param $key
     *
     * @return ilCachedLanguage
     */
    public static function getInstance($key)
    {
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($key);
        }

        return self::$instances[$key];
    }


    public function flush()
    {
        if ($this->global_cache->isActive()) {
            $this->global_cache->flush();
        }
        $this->readFromDB();
        $this->writeToCache();
        // fau: cacheByCode - call writeToCode when cache is flushed
        $this->writeToCode();
        // fau.
    }


    /**
     * @param string $language_key
     */
    public function setLanguageKey($language_key)
    {
        $this->language_key = $language_key;
    }


    /**
     * @return string
     */
    public function getLanguageKey()
    {
        return $this->language_key;
    }


    /**
     * @param boolean $loaded
     */
    public function setLoaded($loaded)
    {
        $this->loaded = $loaded;
    }


    /**
     * @return boolean
     */
    public function getLoaded()
    {
        return $this->loaded;
    }


    /**
     * @param array $translations
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
    }


    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }
}
