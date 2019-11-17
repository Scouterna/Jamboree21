/*!
 * VisualEditor DataModel CheckListNode class.
 *
 * @copyright 2011-2019 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * DataModel list node.
 *
 * @class
 * @extends ve.dm.BranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.CheckListNode = function VeDmCheckListNode() {
	// Parent constructor
	ve.dm.CheckListNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.CheckListNode, ve.dm.BranchNode );

/* Static Properties */

ve.dm.CheckListNode.static.name = 'checkList';

ve.dm.CheckListNode.static.childNodeTypes = [ 'checkListItem' ];

ve.dm.CheckListNode.static.matchTagNames = [ 'ul' ];

ve.dm.CheckListNode.static.matchRdfaTypes = [ 've:checkList' ];

ve.dm.CheckListNode.static.isDiffedAsList = true;

/**
 * Creates a list item element
 *
 * @return {Object} Element data
 */
ve.dm.CheckListNode.static.createItem = function () {
	return { type: 'checkListItem', attributes: { checked: false } };
};

ve.dm.CheckListNode.static.toDomElements = function ( dataElement, doc ) {
	var list = doc.createElement( 'ul' );
	list.setAttribute( 'rel', 've:checkList' );
	return [ list ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.CheckListNode );
