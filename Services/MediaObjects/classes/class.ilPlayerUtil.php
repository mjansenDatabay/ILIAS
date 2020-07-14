<?php
// fau: jumpMedia - added custom parameters to take a newer version of the media element (can be switched back to standard in ilias 6)
// fau: jumpMedia - included plugin for jump forward and back links

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Audio/Video Player Utility
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup
 */
class ilPlayerUtil
{
    static $initialized = false;

    /**
     * Get local path of jQuery file
     */
    public static function getLocalMediaElementJsPath($custom = true)
    {
        if ($custom) {
            return "./Customizing/libs/mediaelement/build/mediaelement-and-player.min.js";
        }
        else {
            return "./libs/bower/bower_components/mediaelement/build/mediaelement-and-player.min.js";
        }
    }

    /**
     * Get local path of jQuery file
     */
    public static function getLocalMediaElementCssPath($custom = true)
    {
        if ($custom) {
            return "./Customizing/libs/mediaelement/build/mediaelementplayer.min.css";
        }
        else {
            return "./libs/bower/bower_components/mediaelement/build/mediaelementplayer.min.css";
        }
    }

    /**
     * Init mediaelement.js scripts
     */
    public static function initMediaElementJs($a_tpl = null, $custom = true)
    {
        if (self::$initialized) {
            return;
        }

        global $DIC;

        $tpl = $DIC["tpl"];

        if ($a_tpl == null) {
            $a_tpl = $tpl;
        }
        
        foreach (self::getJsFilePaths($custom) as $js_path) {
            $a_tpl->addJavaScript($js_path);
        }
        foreach (self::getCssFilePaths($custom) as $css_path) {
            $a_tpl->addCss($css_path);
        }

        self::$initialized = true;
    }
    
    /**
     * Get css file paths
     *
     * @param
     * @return
     */
    public static function getCssFilePaths($custom = true)
    {
        if ($custom) {
            return array(self::getLocalMediaElementCssPath(true),
                "./Customizing/libs/mediaelement_plugins/dist/skip-back/skip-back.min.css",
                "./Customizing/libs/mediaelement_plugins/dist/jump-forward/jump-forward.min.css");
        }
        else {
            return array(self::getLocalMediaElementCssPath(false));
        }
    }
    
    /**
     * Get js file paths
     *
     * @param
     * @return
     */
    public static function getJsFilePaths($custom = true)
    {
        if ($custom) {
            return array(self::getLocalMediaElementJsPath(true),
                "./Customizing/libs/mediaelement_plugins/dist/skip-back/skip-back.min.js",
                "./Customizing/libs/mediaelement_plugins/dist/jump-forward/jump-forward.js");
        }
        else {
            return array(self::getLocalMediaElementJsPath(false));
        }
    }
    

    /**
     * Get flash video player directory
     *
     * @return
     */
    public static function getFlashVideoPlayerDirectory($custom = true)
    {
        if ($custom) {
            return "Customizing/libs/mediaelement/build";
        }
        else {
            return "./libs/bower/bower_components/mediaelement/build";
        }
    }
    
    
    /**
     * Get flash video player file name
     *
     * @return
     */
    public static function getFlashVideoPlayerFilename($a_fullpath = false)
    {
        $file = "flashmediaelement.swf";
        if ($a_fullpath) {
            return self::getFlashVideoPlayerDirectory() . "/" . $file;
        }
        return $file;
    }
    
    /**
     * Copy css files to target dir
     *
     * @param
     * @return
     */
    public static function copyPlayerFilesToTargetDirectory($a_target_dir, $custom = true)
    {
        ilUtil::rCopy(
        self::getFlashVideoPlayerDirectory($custom),
            $a_target_dir
        );
    }
}
