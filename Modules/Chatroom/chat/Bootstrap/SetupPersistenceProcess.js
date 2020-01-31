var Container = require('../AppContainer');
var schedule = require('node-schedule');
var async = require('async');

module.exports = function SetupPersistenceProcess(result, callback) {
	Container.getLogger().info("[Boot process]: Setup persistence job started!");

	schedule.scheduleJob('*/5 * * * * *', function persistDataProcess() {
		var namespaces = Container.getNamespaces();

		async.each(namespaces, function (namespace, nextNamespace) {
			Container.getLogger().debug("[Scheduled Job]: Persist data for namespace %s!", namespace.getName());
			namespace.getDatabase().persist(namespace);
			nextNamespace();
		});
	})

	Container.getLogger().info("[Boot process]: Setup persistence job done!");
	callback();
};
