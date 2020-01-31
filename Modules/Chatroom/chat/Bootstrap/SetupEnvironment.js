var CONST = require('../Constants');
var Container = require('../AppContainer');
var Winston = require('winston');
var Util = require('util');


/**
 * @param {Function} callback
 */
module.exports = function SetupEnvironment(result, callback) {

	var logger = new (Winston.Logger)({
		transports: [
			new (Winston.transports.File)({
				name: 'log',
				filename: Container.getServerConfig().log,
				level: Container.getServerConfig().log_level,
				json: false,
				timestamp: function () {
					var date = new Date();
					return date.toDateString() + ' ' + date.toTimeString();
				},
				formatter: function (options) {
					return Util.format(
						'[%s] %s - %s %s',
						options.timestamp(),
						options.level.toUpperCase(),
						options.message,
						(options.meta !== undefined && options.meta.length > 0) ? '\n\t' + JSON.stringify(options.meta) : '');
				}
			})
		]
	});

	Winston.handleExceptions(
		new (Winston.transports.File)({
			name: 'errorlog',
			filename: Container.getServerConfig().error_log,
			handleExceptions: true,
			humanReadableUnhandledException: true,
			json: false,
			timestamp: function () {
				var date = new Date();
				return date.toDateString() + ' ' + date.toTimeString();
			},
			formatter: function (options) {
				return Util.format(
					'[%s] %s - %s \n %s',
					options.timestamp(),
					options.level.toUpperCase(),
					JSON.stringify(options.meta.process),
					options.meta.stack.join('\n')
				)
			}
		})
	);

	logger.exitOnError = false;
	logger.info('Booting Chat Server!');
	logger.log('debug', '[Boot process]: ServerConfig %s, ', JSON.stringify(Container.getServerConfig()))
	logger.log('debug', '[Boot process]: ClientConfigs %s, ', JSON.stringify(Container.getClientConfigs()))

	Container.setLogger(logger);

	callback(null);
};