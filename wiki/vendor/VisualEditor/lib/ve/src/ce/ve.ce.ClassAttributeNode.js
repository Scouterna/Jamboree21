/*!
 * VisualEditor ContentEditable ClassAttributeNode class.
 *
 * @copyright 2011-2019 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * ContentEditable class-attribute node.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {jQuery} [$classedElement=this.$element] Element to which attribute-based classes are attached
 */
ve.ce.ClassAttributeNode = function VeCeClassAttributeNode( $classedElement ) {
	// Properties
	this.$classedElement = $classedElement || this.$element;
	this.currentAttributeClasses = '';

	this.$classedElement
		// Clear all but unrecognized classes. Attributes classes will be applied
		// correctly on setup.
		.removeClass( this.getModel().getAttribute( 'originalClasses' ) )
		.addClass( this.getModel().getAttribute( 'unrecognizedClasses' ) );

	// Events
	this.connect( this, { setup: 'updateAttributeClasses' } );
	this.model.connect( this, { attributeChange: 'updateAttributeClasses' } );
};

/* Inheritance */

OO.initClass( ve.ce.ClassAttributeNode );

/**
 * Update classes from attributes
 */
ve.ce.ClassAttributeNode.prototype.updateAttributeClasses = function () {
	this.$classedElement.removeClass( this.currentAttributeClasses );
	this.currentAttributeClasses = this.model.constructor.static.getClassAttrFromAttributes( this.model.element.attributes );
	this.$classedElement.addClass( this.currentAttributeClasses );
};
