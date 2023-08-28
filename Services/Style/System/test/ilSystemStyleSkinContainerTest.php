<?php

include_once("Services/Style/System/classes/Utilities/class.ilSkinStyleXML.php");
include_once("Services/Style/System/classes/Utilities/class.ilSkinXML.php");
include_once("Services/Style/System/classes/Utilities/class.ilSystemStyleSkinContainer.php");
include_once("Services/Style/System/test/fixtures/mocks/ilSystemStyleConfigMock.php");
include_once("Services/Style/System/test/fixtures/mocks/ilSystemStyleDICMock.php");

include_once("Services/Style/System/classes/Utilities/class.ilSystemStyleMessageStack.php");
include_once("Services/Utilities/classes/class.ilUtil.php");

use PHPUnit\Framework\TestCase;

/**
 *
 * @author            Timon Amstutz <timon.amstutz@ilub.unibe.ch>
 * @version           $Id$*
 */
class ilSystemStyleSkinContainerTest extends TestCase
{


    /**
     * @var ilSkinXML
     */
    protected $skin;

    /**
     * @var ilSkinStyleXML
     */
    protected $style1 = null;

    /**
     * @var ilSkinStyleXML
     */
    protected $style2 = null;

    /**
     * @var ilSystemStyleConfigMock
     */
    protected $system_style_config;

    protected $save_dic = null;

    protected function setUp() : void
    {
        global $DIC;

        $this->save_dic = is_object($DIC) ? clone $DIC : $DIC;

        $DIC = new \ILIAS\DI\Container();

        $DIC['lng'] = function (\ILIAS\DI\Container $c) {
            return $this->getMockBuilder(ilLanguage::class)->disableOriginalConstructor()->getMock();
        };
        $DIC['ilDB'] = function (\ILIAS\DI\Container $c) {
            $db = $this->createMock(ilDBInterface::class);

            $db->method('quote')->willReturnArgument(0);

            $mock = null;
            $db->method('query')->willReturnCallback(function ($query) use (&$mock) {
                if (strpos($query, 'type = facs') !== false) {
                    $mock = $this->createMock(ilDBStatement::class);
                    $mock->method('rowCount')->willReturn(1);
                    $mock->method('fetch')->willReturn(
                        [
                            'obj_id' => 4711,
                            'description' => 'phpunit'
                        ],
                        null
                    );
                }

                if (strpos($query, 'obj_id = 4711') !== false) {
                    $mock = $this->createMock(ilDBStatement::class);
                    $mock->method('rowCount')->willReturn(1);
                    $mock->method('fetch')->willReturnOnConsecutiveCalls(
                        ['ref_id' => 666],
                        null
                    );
                }

                return $this->createMock(ilDBStatement::class);
            });


            $db->method('numRows')->willReturnCallback(function () use (&$mock) {
                return $mock->rowCount();
            });
            $db->method('fetchAssoc')->willReturnCallback(function () use (&$mock) {
                return $mock->fetch(PDO::FETCH_ASSOC);
            });

            return $db;
        };
        $DIC['rbacsystem'] = function (\ILIAS\DI\Container $c) {
            $rbac_system = $this->getMockBuilder(ilRbacSystem::class)->disableOriginalConstructor()->getMock();
            $rbac_system->method('checkAccess')->willreturn(false);

            return $rbac_system;
        };
        $DIC['ilSetting'] = function (\ILIAS\DI\Container $c) {
            $settings = $this->getMockBuilder(ilSetting::class)->disableOriginalConstructor()->getMock();
            $settings->method('get')->willReturnCallback(function (string $keyword) {
                if ($keyword === 'suffix_custom_expl_black') {
                    return 'php';
                }

                return $keyword;
            });

            return $settings;
        };
        $DIC['ilLoggerFactory'] = function (\ILIAS\DI\Container $c) {
            $logger = $this->getMockBuilder(ilLogger::class)->disableOriginalConstructor()->getMock();

            $logger_factory = new class extends ilLoggerFactory {
                public static $logger;

                public function __construct()
                {
                }

                public static function getRootLogger() : ilLogger
                {
                    return self::$logger;
                }
            };
            $logger_factory::$logger = $logger;

            return $logger_factory;
        };

        if (!defined('PATH_TO_LESSC')) {
            if (file_exists("ilias.ini.php")) {
                $ini = parse_ini_file("ilias.ini.php", true);
                define('PATH_TO_LESSC', $ini['tools']['lessc']);
            } else {
                define('PATH_TO_LESSC', "");
            }
        }

        $this->skin = new ilSkinXML("skin1", "skin 1");

        $this->style1 = new ilSkinStyleXML("style1", "Style 1");
        $this->style1->setCssFile("style1css");
        $this->style1->setImageDirectory("style1image");
        $this->style1->setSoundDirectory("style1sound");
        $this->style1->setFontDirectory("style1font");

        $this->style2 = new ilSkinStyleXML("style2", "Style 2");
        $this->style2->setCssFile("style2css");
        $this->style2->setImageDirectory("style2image");
        $this->style2->setSoundDirectory("style2sound");
        $this->style2->setFontDirectory("style2font");

        $this->system_style_config = new ilSystemStyleConfigMock();

        mkdir($this->system_style_config->test_skin_temp_path);
        ilSystemStyleSkinContainer::xCopy($this->system_style_config->test_skin_original_path, $this->system_style_config->test_skin_temp_path);
    }

    protected function tearDown() : void
    {
        global $DIC;
        $DIC = $this->save_dic;

        ilSystemStyleSkinContainer::recursiveRemoveDir($this->system_style_config->test_skin_temp_path);
    }

    public function testGenerateFromId()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $this->assertEquals($container->getSkin()->getId(), $this->skin->getId());
        $this->assertEquals($container->getSkin()->getName(), $this->skin->getName());

        $this->assertEquals($container->getSkin()->getStyle($this->style1->getId()), $this->style1);
        $this->assertEquals($container->getSkin()->getStyle($this->style2->getId()), $this->style2);
    }

    public function testCreateDelete()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);

        $container->getSkin()->setId("newSkin");
        $container->create(new ilSystemStyleMessageStack());

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . "newSkin"));
        $container->delete();
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . "newSkin"));
    }

    public function testUpdateSkinNoIdChange()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $container->updateSkin();
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId()));
    }

    public function testUpdateSkinWithChangedID()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $old_skin = clone $container->getSkin();
        $container->getSkin()->setId("newSkin2");
        $container->updateSkin($old_skin);
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . "newSkin2"));
        $old_skin = clone $container->getSkin();
        $container->getSkin()->setId($this->skin->getId());
        $container->updateSkin($old_skin);
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . "newSkin2"));
    }

    public function testAddStyle()
    {
        $new_style = new ilSkinStyleXML("style1new", "new Style");
        $new_style->setCssFile("style1new");
        $new_style->setImageDirectory("style1newimage");
        $new_style->setSoundDirectory("style1newsound");
        $new_style->setFontDirectory("style1newfont");

        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1image"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1sound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1font"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css-variables.less"));

        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newimage"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newsound"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newfont"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.css"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.less"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new-variables.less"));

        $container->addStyle($new_style);

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1image"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1sound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1font"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css-variables.less"));

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newimage"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newsound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newfont"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new-variables.less"));
    }

    public function testDeleteStyle()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);

        $container->deleteStyle($this->style1);

        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1image"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1sound"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1font"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.css"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.less"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css-variables.less"));

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2image"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2sound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2font"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2css.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2css.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style2css-variables.less"));
    }

    public function testUpdateStyle()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $skin = $container->getSkin();

        $old_style = clone $skin->getStyle($this->style1->getId());
        $new_style = $skin->getStyle($this->style1->getId());

        $new_style->setId("style1new");
        $new_style->setName("new Style");
        $new_style->setCssFile("style1new");
        $new_style->setImageDirectory("style1newimage");
        $new_style->setSoundDirectory("style1newsound");
        $new_style->setFontDirectory("style1newfont");

        $container->updateStyle($new_style->getId(), $old_style);

        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1image"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1sound"));
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1font"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.css"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css.less"));
        $this->assertFalse(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1css-variables.less"));

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newimage"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newsound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1newfont"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $this->skin->getId() . "/style1new-variables.less"));
    }

    public function testDeleteSkin()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $skin = $container->getSkin();

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId()));
        $container->delete();
        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId()));
    }

    public function testCopySkin()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $skin = $container->getSkin();

        $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId() . "Copy"));

        $container_copy = $container->copy();
        $skin_copy = $container_copy->getSkin();

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId() . "Copy"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId()));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1image"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1sound"));
        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1font"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css.css"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css.less"));
        $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css-variables.less"));

        $this->assertEquals($skin->getName() . " Copy", $skin_copy->getName());
        $this->assertEquals("0.1", $skin_copy->getVersion());
    }

    public function testCopySkinWithInjectedName()
    {
        $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
        $skin = $container->getSkin();
        $container_copy = $container->copy("inject");
        $skin_copy = $container_copy->getSkin();

        $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId() . "inject"));
        $this->assertEquals($skin->getName() . " inject", $skin_copy->getName());
        $this->assertEquals("0.1", $skin_copy->getVersion());
    }

    public function testImportSkin()
    {
        if (!defined('PATH_TO_ZIP')) {
            if (file_exists("ilias.ini.php")) {
                $ini = parse_ini_file("ilias.ini.php", true);
                define('PATH_TO_ZIP', $ini['tools']['zip']);
            } elseif (is_executable("/usr/bin/zip")) {
                define('PATH_TO_ZIP', "/usr/bin/zip");
            } else {
                define('PATH_TO_ZIP', "");
            }
        }

        if (!defined('PATH_TO_UNZIP')) {
            if (file_exists("ilias.ini.php")) {
                $ini = parse_ini_file("ilias.ini.php", true);
                define('PATH_TO_UNZIP', $ini['tools']['unzip']);
            } elseif (is_executable("/usr/bin/unzip")) {
                define('PATH_TO_UNZIP', "/usr/bin/unzip");
            } else {
                define('PATH_TO_UNZIP', "");
            }
        }

        //Only perform this test, if an unzip and zip path has been found.
        if (PATH_TO_UNZIP !== "" && PATH_TO_ZIP !== "") {
            $container = ilSystemStyleSkinContainer::generateFromId($this->skin->getId(), null, $this->system_style_config);
            $skin = $container->getSkin();

            $this->assertFalse(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId() . "Copy"));

            $container_import = $container->import($container->createTempZip(), $this->skin->getId() . ".zip", null, $this->system_style_config, false);
            $skin_copy = $container_import->getSkin();

            $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin->getId() . "Copy"));
            $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId()));
            $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1image"));
            $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1sound"));
            $this->assertTrue(is_dir($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1font"));
            $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css.css"));
            $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css.less"));
            $this->assertTrue(is_file($this->system_style_config->getCustomizingSkinPath() . $skin_copy->getId() . "/style1css-variables.less"));
        } else {
            $this->markTestIncomplete('No unzip has been detected on the system');
        }
    }
}
