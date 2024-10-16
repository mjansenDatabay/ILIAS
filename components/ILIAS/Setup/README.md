# Setup Machinery

This library contains interfaces, base classes and utilities for the setup.

The setup is build around four concepts:

* [**Config**](./Config.php) - Some options or configuration for the setup process
that a user can or must set.
* [**Objective**](./Objective.php) - Some desired state of the system that should
be achieved via the setup process, maybe depending on other objectives as preconditions.
* [**Agent**](./Agent.php) - Some component performing parts of in the setup process
is refered to as agent.
* [**Environment**](./Environment.php) - Some surrounding of the setup process which
the objectives build and depend upon.

Any implementation of a setup process, on the command line or in the web, then
basically needs to ask an agent for an objective for a fresh installation (or the
update of an installation) and then successively achieve all the preconditions
and finally the objective itself.

There are special kinds of `Objective`s and supporting classes that are tailored to
match certain use cases.

* A [**Migration**](Migration.php) is a potentially long running operation that can be
broken into discrete steps. Other than database updates, it is supposed to run in the
background, even when the installation is online again.
* [**BuildArtifactObjective**](Objective/BuildArtifactObjective.php) allows to create an
[`Artifact`](./Artifact.php) somewhere. Look into the [according section](#on-artifacts)
to find out how to use them.

For the `status` command of the setup, the kernel of a framework for metrics is
[included](./Metrics/README.md) here. This is kept a little separate from the rest
of the setup, because we might want to detach this some day.


## More Details, Please!

### On Config

This comes first, because it's probably the most simple of the four concepts. A
config is basically a glorified key-value map as a data type. It encapsulates
defaults and checks for the values in the config and acts as an insurance to its
users that the config has the expected shape. Also, it's a config for the setup
process and not for the installed system. For an example, have a look into
[`ilDatabaseSetupConfig`](Services/Database/classes/Setup/class.ilDatabaseSetupConfig.php).

A config-file, when used from the CLI, expects keys according to the array keys used
for the construction of the AgentCollection in cli.php ($c["agent"] = ...).
So, e.g., constructing your colllection with "database" => $c["agent.database"] means
an expected config
{
  "database" : {
      "host": "xxx",
      "port": "",
      ...
  }
}


### On Environment

This basically is a key-value map as well, but with resources as values. It acts
as a registry for the services that are required and created during the setup
process, e.g. the database. A complete environment for an ILIAS-installation is
the ultimate goal of the setup process. Since the setup process starts with very
little, the environment is designed as an extensible registry that will get
filled during the setup process. Look into [`ilDatabaseExistsObjective::achieve`](Services/Database/classes/Setup/class.ilDatabaseExistsObjective.php)
to see how the environment is used during the setup process.


### On Agent

An `Agent` is what every ILIAS-component needs to implement if it wants to take
part in the setup process. An agent needs to tell how to build a configuration
from an array or by an input from the UI framework. It also needs to provide an
objective for the setup or for an update. As expected, the database-service
provides an agent for the setup: [`ilDatabaseSetupAgent`](Services/Database/classes/Setup/class.ilDatabaseSetupAgent.php).


### On Objective

Objectives are the core of the whole matter. An `Objective` describes a state of
the system that an agent wants to achieve. An objective might or might not be
applicable for the current state of the system, which means that it might not
be required to be achieved. Any `Objective` may have preconditions, which are
other objectives. Once the preconditions are achieved, the objective itself may
be achieved. This might use stuff from the environment but also add stuff to
the environment. The [agent from the database service](Services/Database/classes/Setup/class.ilDatabaseSetupAgent.php),
for example, has the [objective to create a populated database](Services/Database/classes/Setup/class.ilDatabasePopulatedObjective.php).
This has the precondition [that the database exists](Services/Database/classes/Setup/class.ilDatabaseExistsObjective.php),
which in turn requires [that the database server is connectable](Services/Database/classes/Setup/class.ilDatabaseExistsObjective.php).

This yields a directed graph of objectives, where (hopefully) some objectives do
not have any preconditions. These can be achieved, which prepares the environment
for other objectives to be achievable, until all objectives are achieved and the
setup is completed.


### On Migration

Sometimes an update of an installation requires more work than simply downloading
fresh code and updating the database schema. When, e.g., the certificates where
moved to a new persistant storage model, a lot of data needed to be shuffled around.
This operation would potentially take a lot of time and thus was offloaded to be
triggered by single users.

The setup offers functionality for components to encapsulate these kind of operations
to allow administrators to monitor and also run them in a principled way. `Agent`s
therefore can implement the [`getMigrations`](`src/Setup/Agent.php#L82`) method to
make these [`Migration`s](src/Setup/Migration.php) available in the setup.

The general idea is, that a migration is an operation that can be broken into discrete
steps which can be executed even if the installation is online after update again.
These steps can then be triggered via the CLI and also be monitored there. It is well
possible, that there are also other means to trigger the steps, such as an interaction
by the user. The first user of the migrations is the [`FileObject`](Modules/File/classes/Setup/class.ilFileObjectToStorageMigration.php).


### On Artifact

Sometimes ILIAS needs information from the source code to offer certain services.
E.g.: Which base classes and command classes exist in the control structure?
Which GlobalScreen-providers exist to build the screen? Which instances of
WebAccessChecker are available. Since this information can be derived statically
for any given state of source code, it would be inefficient to derive it dynamically.

The [`BuildArtifactObjective`](Objective/BuildArtifactObjective.php) allows to create source-code
files based on the current state of the code and store them in the ILIAS-filesystem-
structure for later use.

This strategy will be faster then crawling the ILIAS code everytime the information
is required or storing that information in the database. Thanks to op-code-caching,
the information will practically be in-memory. This approach has one major downside:
When adding or changing code that is included in some artifact, the change does
not come in effect immediately, because the corresponding artifact has not been
updated. This is done via `php cli/setup.php build` or when updating
the composer class-map.

You can use your artifact with the following method which resolves to the 
path whre the artifact is stored:

```php
    $array_data = require MyArtifact::PATH();
```

#### Example: Global Screen Provider

The main visuals of ILIAS are pieced together by parts from many different components.
Entries in the main bar may be derived from various components, notifications arise
from many sources and tools are provided by different features. The [GlobalScreen-service](../../components/ILIAS/GlobalScreen_)
collects providers from all components to build the screen from contributions from
all of them. Providers are classes implementing a specific interface. These are
collected in the [`ilGlobalScreenBuildProviderMapObjective`](../../components/ILIAS/GlobalScreen_/classes/Setup/class.ilGlobalScreenBuildProviderMapObjective.php)
and stored in `components/ILIAS/GlobalScreen_/artifacts/global_screen_providers.php` as
serialized array like so:

```php
<?php return array (
  'ILIAS\\GlobalScreen\\Scope\\MainMenu\\Provider\\StaticMainMenuProvider' =>
  array (
    0 => 'ilLearningHistoryGlobalScreenProvider',

	//...

    18 => 'ilPrtfGlobalScreenProvider',
  ),
  'ILIAS\\GlobalScreen\\Scope\\MetaBar\\Provider\\StaticMetaBarProvider' =>
  array (
    0 => 'ilSearchGSMetaBarProvider',
    1 => 'ilMMCustomTopBarProvider',
  ),
  'ILIAS\\GlobalScreen\\Scope\\Tool\\Provider\\DynamicToolProvider' =>
  array (
    0 => 'ilStaffGSToolProvider',
    1 => 'ilMediaPoolGSToolProvider',
  ),
);
```

The GlobalScreen-service than reads that file later and uses the information to
determine which classes to use for which task:

```php
	/**
	 * @inheritDoc
	 */
	public function __construct(Container $dic) {
		// ...
		$this->class_loader = include "vendor/ilias/Artifacts/global_screen_providers.php";
	}
```
