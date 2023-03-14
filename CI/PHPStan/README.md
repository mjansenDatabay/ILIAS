PHPStan Custom Rules
====================

With the ["Removing of Legacy-UIComponents-Service and Table" project](https://docu.ilias.de/goto_docu_grp_12110.html), a large number of UI elements that are not available in the UI service will be replaced by ILIAS 10. With the rules collected here, violations of the deprecations are found and collected in reports.

The entire report comprises a CSV file for each component and a summarised file for the entire code base, this form of the report can be generated as follows:

```bash
./CI/PHPStan/run_legacy_ui_report.sh
```

All results will be written to the directory `./Reports`. 

To run the rules individually (e.g. for the directory Modules/File), the following command can be used:

```bash
./CI/PHPStan/run_legacy_ui_report.sh Modules/File
```

If you want to just check and show violations directly (without csv-report), you can use the following command (for Modules/File):

```bash
./libs/composer/vendor/bin/phpstan analyse -c ./CI/PHPStan/legacy_ui.neon -a ./libs/composer/vendor/autoload.php --no-interaction --no-progress Modules/File 
```

# Example Configuration File to include Custom Rules and Services:

```yaml
includes:
  - CI/PHPStan/phpstan.neon
  - phpstan-baseline.neon
parameters:
  level: 6
rules:
  - ILIAS\CI\PHPStan\Rules\NoTriggerErrorFunctionCallRule
  - ILIAS\CI\PHPStan\Rules\NoSilenceOperatorRule
  - ILIAS\CI\PHPStan\Rules\NoScriptTerminationRule
  - ILIAS\CI\PHPStan\Rules\NoEvalFunctionCallRule
  - ILIAS\CI\PHPStan\Rules\NoDatabaseUsageInControllersRule
  - ILIAS\CI\PHPStan\Rules\NoGlobalsExceptDicRule
  - ILIAS\CI\PHPStan\Rules\NoArrayAccessOnGlobalsExceptDicRule
  - ILIAS\CI\PHPStan\Rules\NoUserInterfaceComponentsInNonControllersRule
services:
  -
    class: ILIAS\CI\PHPStan\services\SuffixBasedControllerDetermination
    arguments:
      suffix: "GUI"
```