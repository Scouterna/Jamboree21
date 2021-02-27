'use strict';
var Button = require( '../mobile.startup/Button' ),
	View = require( '../mobile.startup/View' ),
	Icon = require( '../mobile.startup/Icon' ),
	okButton = new Button( {
		label: mw.msg( 'ok' ),
		tagName: 'button',
		progressive: true,
		additionalClassNames: 'cancel'
	} ),
	util = require( '../mobile.startup/util' );

/**
 * @extends View
 */
class BlockMessageDetails extends View {
	/** @inheritdoc */
	get isTemplateMode() {
		return true;
	}
	/**
	 * @inheritdoc
	 */
	get defaults() {
		return {
			createDetailsAnchorHref: function () {
				return function ( blockId, render ) {
					return mw.util.getUrl( 'Special:BlockList', { wpTarget: '#' + render( blockId ) } );
				};
			},
			createDetailsAnchorLabel: function () {
				return mw.msg( 'mobile-frontend-editor-blocked-drawer-help' );
			},
			createTitle: function () {
				return this.partial ? mw.msg( 'mobile-frontend-editor-blocked-drawer-title-partial' ) : mw.msg( 'mobile-frontend-editor-blocked-drawer-title' );
			},
			reasonHeader: mw.msg( 'mobile-frontend-editor-blocked-drawer-reason-header' ),
			creatorHeader: function () {
				// The gender is the subject (the blockee) not the object (the blocker).
				return mw.msg( 'mobile-frontend-editor-blocked-drawer-creator-header',
					mw.user.options.get( 'gender' ) );
			},
			expiryHeader: mw.msg( 'mobile-frontend-editor-blocked-drawer-expiry-header' )
		};
	}
	/**
	 * @inheritdoc
	 */
	postRender() {
		const userIcon = new Icon( {
			tagName: 'span',
			name: 'userAvatar',
			hasText: true,
			label: this.options.creator.name
		} );

		this.$el.find( '.block-message-creator a' ).prepend(
			userIcon.$el
		);
		this.$el.find( '.block-message-buttons' ).prepend(
			okButton.$el
		);
		this.$el.find( '.block-message-icon' ).prepend(
			( new Icon( {
				name: 'stopHand-destructive',
				additionalClassNames: 'mw-ui-icon-flush-top'
			} ) ).$el
		);
	}
	/**
	 * @inheritdoc
	 */
	get template() {
		return util.template( `
<div class="block-message">
  <div class="block-message-icon"></div>
  <div class="block-message-info">
    <div class="block-message-item block-message-title">
      <h5>{{ createTitle }}</h5>
    </div>
    <div class="block-message-data">
      {{#reason}}
        <div class="block-message-item">
          <h6>{{ reasonHeader }}</h6>
          <div><strong>{{{ reason }}}</strong></div>
        </div>
      {{/reason}}
      <div class="block-message-item block-message-creator">
        {{#creator.name}}
          <h6>{{ creatorHeader }}</h6>
          <div>
            <strong>
              {{#creator.url}}
                <a href="{{ creator.url }}"></a>
              {{/creator.url}}
              {{^creator.url}}
                {{ creator.name }}
              {{/creator.url}}
            </strong>
          </div>
        {{/creator.name}}
      </div>
      {{#expiry}}
        <div class="block-message-item">
          <h6>{{ expiryHeader }}</h6>
          <div><strong>{{#duration}}{{ duration }}{{/duration}} {{ expiry }}</strong></div>
        </div>
      {{/expiry}}
    </div>
    <div class="block-message-item block-message-buttons">
      {{#blockId}}
        <a href="{{#createDetailsAnchorHref}}{{ blockId }}{{/createDetailsAnchorHref}}">
          {{ createDetailsAnchorLabel }}
        </a>
      {{/blockId}}
    </div>
  </div>` );
	}
}

module.exports = BlockMessageDetails;
