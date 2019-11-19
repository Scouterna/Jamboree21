/*!
 * VisualEditor MediaWiki event subscriber.
 *
 * Subscribes to ve.track() events and routes them to mw.track().
 *
 * @copyright 2011-2019 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

( function () {
	var timing, editingSessionId,
		actionPrefixMap = {
			saveIntent: 'save_intent',
			saveAttempt: 'save_attempt',
			saveSuccess: 'save_success',
			saveFailure: 'save_failure'
		},
		trackdebug = !!mw.Uri().query.trackdebug;

	timing = {};
	editingSessionId = mw.user.generateRandomSessionId();

	function log() {
		// mw.log is a no-op unless resource loader is in debug mode, so
		// this allows trackdebug to work independently (T211698)
		// eslint-disable-next-line no-console
		console.log.apply( console, arguments );
	}

	function inSample() {
		// Not using mw.eventLog.inSample() because we need to be able to pass our own editingSessionId
		return mw.eventLog.randomTokenMatch(
			1 / mw.config.get( 'wgWMESchemaEditAttemptStepSamplingRate' ),
			editingSessionId
		);
	}

	function computeDuration( action, event, timeStamp ) {
		if ( event.timing !== undefined ) {
			return event.timing;
		}

		switch ( action ) {
			case 'ready':
				return timeStamp - timing.init;
			case 'loaded':
				return timeStamp - timing.init;
			case 'saveIntent':
				return timeStamp - timing.ready;
			case 'saveAttempt':
				return timeStamp - timing.saveIntent;
			case 'saveSuccess':
			case 'saveFailure':
				// HERE BE DRAGONS: the caller must compute these themselves
				// for sensible results. Deliberately sabotage any attempts to
				// use the default by returning -1
				mw.log.warn( 've.init.mw.trackSubscriber: Do not rely on default timing value for saveSuccess/saveFailure' );
				return -1;
			case 'abort':
				switch ( event.type ) {
					case 'preinit':
						return timeStamp - timing.init;
					case 'nochange':
					case 'switchwith':
					case 'switchwithout':
					case 'switchnochange':
					case 'abandon':
						return timeStamp - timing.ready;
					case 'abandonMidsave':
						return timeStamp - timing.saveAttempt;
				}
		}
		mw.log.warn( 've.init.mw.trackSubscriber: Unrecognized action', action );
		return -1;
	}

	function mwEditHandler( topic, data, timeStamp ) {
		var action = topic.split( '.' )[ 1 ],
			actionPrefix = actionPrefixMap[ action ] || action,
			duration = 0,
			event;

		timeStamp = timeStamp || this.timeStamp; // I8e82acc12 back-compat

		if ( action === 'init' ) {
			// Regenerate editingSessionId
			editingSessionId = mw.user.generateRandomSessionId();
		}

		if ( !inSample() && !mw.config.get( 'wgWMESchemaEditAttemptStepOversample' ) && !trackdebug ) {
			return;
		}

		if (
			action === 'abort' &&
			( data.type === 'unknown' || data.type === 'unknown-edited' )
		) {
			if (
				timing.saveAttempt &&
				timing.saveSuccess === undefined &&
				timing.saveFailure === undefined
			) {
				data.type = 'abandonMidsave';
			} else if (
				timing.init &&
				timing.ready === undefined
			) {
				data.type = 'preinit';
			} else if ( data.type === 'unknown' ) {
				data.type = 'nochange';
			} else {
				data.type = 'abandon';
			}
		}

		// Convert mode=source/visual to interface name
		if ( data && data.mode ) {
			// eslint-disable-next-line camelcase
			data.editor_interface = data.mode === 'source' ? 'wikitext-2017' : 'visualeditor';
			delete data.mode;
		}

		if ( !data.platform ) {
			if ( ve.init && ve.init.target && ve.init.target.constructor.static.platformType ) {
				data.platform = ve.init.target.constructor.static.platformType;
			} else {
				data.platform = 'other';
				// TODO: outright abort in this case, once we think we've caught everything
				mw.log.warn( 've.init.mw.trackSubscriber: no target available and no platform specified', action );
			}
		}

		/* eslint-disable camelcase */
		event = $.extend( {
			version: 1,
			action: action,
			is_oversample: !inSample(),
			editor_interface: 'visualeditor',
			integration: ve.init && ve.init.target && ve.init.target.constructor.static.integrationType || 'page',
			page_id: mw.config.get( 'wgArticleId' ),
			page_title: mw.config.get( 'wgPageName' ),
			page_ns: mw.config.get( 'wgNamespaceNumber' ),
			revision_id: mw.config.get( 'wgRevisionId' ),
			editing_session_id: editingSessionId,
			page_token: mw.user.getPageviewToken(),
			session_token: mw.user.sessionId(),
			user_id: mw.user.getId(),
			user_editcount: mw.config.get( 'wgUserEditCount', 0 ),
			mw_version: mw.config.get( 'wgVersion' )
		}, data );

		if ( mw.user.isAnon() ) {
			event.user_class = 'IP';
		}

		event[ actionPrefix + '_type' ] = event.type;
		event[ actionPrefix + '_mechanism' ] = event.mechanism;
		if ( action !== 'init' ) {
			duration = Math.round( computeDuration( action, event, timeStamp ) );
			event[ actionPrefix + '_timing' ] = duration;
		}
		event[ actionPrefix + '_message' ] = event.message;
		/* eslint-enable camelcase */

		// Remove renamed properties
		delete event.type;
		delete event.mechanism;
		delete event.timing;
		delete event.message;

		if ( action === 'abort' ) {
			timing = {};
		} else {
			timing[ action ] = timeStamp;
		}

		if ( trackdebug ) {
			log( topic, duration + 'ms', event );
		} else {
			mw.track( 'event.EditAttemptStep', event );
		}
	}

	function mwTimingHandler( topic, data ) {
		// Add type for save errors; not in the topic for stupid historical reasons
		if ( topic === 'mwtiming.performance.user.saveError' ) {
			topic = topic + '.' + data.type;
		}

		// Map mwtiming.foo --> timing.ve.foo.mobile
		topic = topic.replace( /^mwtiming/, 'timing.ve.' + data.targetName );
		if ( trackdebug ) {
			log( topic, Math.round( data.duration ) + 'ms' );
		} else {
			mw.track( topic, data.duration );
		}
	}

	function activityHandler( topic, data ) {
		var feature = topic.split( '.' )[ 1 ],
			event;

		if ( !inSample() && !trackdebug ) {
			return;
		}

		if ( ve.init.target.constructor.static.platformType === 'phone' ) {
			// handled in MobileFrontend for session-identification reasons
			return;
		}

		event = {
			feature: feature,
			action: data.action,
			editingSessionId: editingSessionId
		};

		if ( trackdebug ) {
			log( topic, event );
		} else {
			mw.track( 'event.VisualEditorFeatureUse', event );
		}
	}

	if ( mw.loader.getState( 'schema.EditAttemptStep' ) !== null || trackdebug ) {
		// Only route any events into the EditAttemptStep schema if the module is actually available.
		// It won't be if EventLogging is installed but WikimediaEvents is not.
		// Also load ext.eventLogging.subscriber to provide mw.eventLog.randomTokenMatch().
		mw.loader.using( 'ext.eventLogging.subscriber' ).done( function () {
			ve.trackSubscribe( 'mwedit.', mwEditHandler );
			ve.trackSubscribe( 'mwtiming.', mwTimingHandler );
		} );
	}

	if ( mw.loader.getState( 'schema.VisualEditorFeatureUse' ) !== null || trackdebug ) {
		// Similarly for the VisualEditorFeatureUse schema
		mw.loader.using( 'ext.eventLogging.subscriber' ).done( function () {
			ve.trackSubscribe( 'activity.', activityHandler );
		} );
	}

}() );
