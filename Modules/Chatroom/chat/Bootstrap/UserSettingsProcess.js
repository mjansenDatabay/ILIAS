var Container = require('../AppContainer');
var schedule = require('node-schedule');

/**
 * @param {Function} callback
 */
module.exports = function UserSettingsProcess(result, callback) {
	Container.getLogger().info("[Boot process]: Setup fetch user settings job started!");

	schedule.scheduleJob('UserSettingsProcess', '*/20 * * * *', function fetchUserSettings() {
		var namespaces = Container.getNamespaces();

		for (var key in namespaces) {
			if (!namespaces.hasOwnProperty(key) || !namespaces[key].isIM()) {
				continue;
			}

			Container.getLogger().info(
				'[Scheduled Job]: Fetching user settings for namespace %s started!',
				namespaces[key].getName()
			);

			var database = namespaces[key].getDatabase();
			var subscribers = namespaces[key].getSubscribers();
			var usersAcceptingMessages = {};

			database.getMessageAcceptanceStatusForUsers(function onConfigRowFound(row) {
				usersAcceptingMessages[row.usr_id] = row.usr_id;
			}, function onCompleteConfigRead() {
				for (var subsKey in subscribers) {
					if (!subscribers.hasOwnProperty(subsKey)) {
						continue;
					}
					subscribers[subsKey].setAcceptsMessages(
						usersAcceptingMessages.hasOwnProperty(subscribers[subsKey].getId())
					);
				}
				Container.getLogger().info(
					'[Scheduled Job]: Fetching user settings for namespace %s finished',
					namespaces[key].getName()
				);
			});
		}
	}).invoke();

	Container.getLogger().info("[Boot process]: Setup fetch user settings job done!");
	callback();
};
