/*!
 * VisualEditor UserInterface LinearContextItem class.
 *
 * @copyright 2011-2019 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Item in a context.
 *
 * @class
 * @extends ve.ui.ContextItem
 * @mixins OO.ui.mixin.PendingElement
 *
 * @constructor
 * @param {ve.ui.Context} context Context item is in
 * @param {ve.dm.Model} [model] Model item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.LinearContextItem = function VeUiLinearContextItem( context, model, config ) {
	config = config || {};

	// Parent constructor
	ve.ui.LinearContextItem.super.apply( this, arguments );

	// Mixin constructors
	OO.ui.mixin.PendingElement.call( this, config );

	// Properties
	this.$head = $( '<div>' );
	this.$title = $( '<div>' );
	this.$actions = $( '<div>' );
	this.$body = $( '<div>' );
	this.$info = $( '<div>' );
	this.$description = $( '<div>' );
	// Don't use mixins as they expect the icon and label to be children of this.$element.
	this.icon = new OO.ui.IconWidget( { icon: config.icon || this.constructor.static.icon } );
	this.label = new OO.ui.LabelWidget( { label: config.label || this.constructor.static.label } );

	if ( !this.context.isMobile() ) {
		// Desktop
		this.editButton = new OO.ui.ButtonWidget( {
			label: ve.msg( this.isReadOnly() ? 'visualeditor-contextitemwidget-label-view' : 'visualeditor-contextitemwidget-label-secondary' ),
			flags: [ 'progressive' ]
		} );
		this.deleteButton = new OO.ui.ButtonWidget( {
			label: ve.msg( 'visualeditor-contextitemwidget-label-remove' ),
			flags: [ 'destructive' ]
		} );
	} else {
		// Mobile
		this.editButton = new OO.ui.ButtonWidget( {
			framed: false,
			icon: this.isReadOnly() ? 'eye' : 'edit',
			flags: [ 'progressive' ]
		} );
		this.deleteButton = new OO.ui.ButtonWidget( {
			framed: false,
			icon: 'trash',
			flags: [ 'destructive' ]
		} );
	}
	this.actionButtons = new OO.ui.ButtonGroupWidget();
	if ( this.isDeletable() ) {
		this.actionButtons.addItems( [ this.deleteButton ] );
	}
	if ( this.isEditable() ) {
		this.actionButtons.addItems( [ this.editButton ] );
	}

	// Events
	this.editButton.connect( this, { click: 'onEditButtonClick' } );
	this.deleteButton.connect( this, { click: 'onDeleteButtonClick' } );

	// Initialization
	this.$description.addClass( 've-ui-linearContextItem-description' );
	this.$info
		.addClass( 've-ui-linearContextItem-info' )
		.append( this.$description );
	this.$title
		.addClass( 've-ui-linearContextItem-title' )
		.append( this.icon.$element, this.label.$element );
	this.$actions
		.addClass( 've-ui-linearContextItem-actions' )
		.append( this.actionButtons.$element );
	this.$head
		.addClass( 've-ui-linearContextItem-head' )
		.append( this.$title, this.$info, this.$actions );
	this.$body.addClass( 've-ui-linearContextItem-body' );
	this.$element
		.addClass( 've-ui-linearContextItem' )
		.append( this.$head, this.$body );
};

/* Inheritance */

OO.inheritClass( ve.ui.LinearContextItem, ve.ui.ContextItem );
OO.mixinClass( ve.ui.ContextItem, OO.ui.mixin.PendingElement );

/* Events */

/**
 * @event command
 */

/* Static Properties */

ve.ui.LinearContextItem.static.editable = true;

ve.ui.LinearContextItem.static.deletable = true;

/**
 * Whether the context item should try (if space permits) to go inside the node,
 * rather than below with an arrow
 *
 * @static
 * @property {boolean}
 * @inheritable
 */
ve.ui.LinearContextItem.static.embeddable = true;

/* Methods */

/**
 * Handle edit button click events.
 *
 * @localdoc Executes the command related to #static-commandName on the context's surface
 *
 * @protected
 */
ve.ui.LinearContextItem.prototype.onEditButtonClick = function () {
	var command = this.getCommand();

	if ( command ) {
		command.execute( this.context.getSurface() );
		this.emit( 'command' );
	}
};

/**
 * Handle delete button click events.
 */
ve.ui.LinearContextItem.prototype.onDeleteButtonClick = function () {
	this.getFragment().removeContent();
};

/**
 * Check if item is editable.
 *
 * @return {boolean} Item is editable
 */
ve.ui.LinearContextItem.prototype.isEditable = function () {
	return this.constructor.static.editable && ( !this.model || this.model.isEditable() );
};

/**
 * Check if item is deletable.
 *
 * @return {boolean} Item is deletable
 */
ve.ui.LinearContextItem.prototype.isDeletable = function () {
	return this.constructor.static.deletable && this.isNode() && this.context.showDeleteButton() && this.isReadOnly();
};

/**
 * Get the description.
 *
 * @localdoc Override for custom description content
 * @return {string} Item description
 */
ve.ui.LinearContextItem.prototype.getDescription = function () {
	return '';
};

ve.ui.LinearContextItem.prototype.setIcon = function ( icon ) {
	return this.icon.setIcon( icon );
};

ve.ui.LinearContextItem.prototype.setLabel = function ( label ) {
	return this.label.setLabel( label );
};

/**
 * Render the body.
 *
 * @localdoc Renders the result of #getDescription, override for custom body rendering
 */
ve.ui.LinearContextItem.prototype.renderBody = function () {
	this.$body.text( this.getDescription() );
};

/**
 * Render the description.
 *
 * @localdoc Renders the result of #getDescription, override for custom description rendering
 */
ve.ui.LinearContextItem.prototype.renderDescription = function () {
	this.$description.text( this.getDescription() );
};

/**
 * @inheritdoc
 */
ve.ui.LinearContextItem.prototype.setup = function () {
	if ( this.context.isMobile() ) {
		this.renderDescription();
	} else {
		this.renderBody();
	}

	return this;
};

/**
 * @inheritdoc
 */
ve.ui.LinearContextItem.prototype.teardown = function () {
	this.$description.empty();
	this.$body.empty();
	return this;
};
