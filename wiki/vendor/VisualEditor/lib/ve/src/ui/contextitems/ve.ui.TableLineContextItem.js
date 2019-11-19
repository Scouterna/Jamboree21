/*!
 * VisualEditor TableLineContextItem class.
 *
 * @copyright 2011-2019 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Context item for a table line (row or column) toolset.
 *
 * @class
 * @abstract
 * @extends ve.ui.ContextItem
 *
 * @param {ve.ui.Context} context Context item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Function} tool Tool class the item is based on
 * @param {Object} config Configuration options
 */
ve.ui.TableLineContextItem = function VeUiTableLineContextItem() {
	// Parent constructor
	ve.ui.TableLineContextItem.super.apply( this, arguments );

	this.actionButton = new OO.ui.ButtonWidget( {
		framed: false,
		classes: [ 've-ui-tableLineContextItem-actionButton' ]
	} );

	this.actionButton.connect( this, { click: 'onActionButtonClick' } );

	// Initialization
	this.$element
		.addClass( 've-ui-tableLineContextItem' )
		.append( this.actionButton.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.TableLineContextItem, ve.ui.ContextItem );

/* Static Properties */

ve.ui.TableLineContextItem.static.name = 'tableLine';

/**
 * Title to use for context item action
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.ui.TableLineContextItem.static.title = null;

/* Methods */

/**
 * Handle action button click events.
 *
 * @localdoc Executes the command related to #static-commandName on the context's surface
 *
 * @protected
 */
ve.ui.TableLineContextItem.prototype.onActionButtonClick = function () {
	var command = this.getCommand();

	if ( command ) {
		command.execute( this.context.getSurface() );
		this.emit( 'command' );
	}
};

/**
 * Get the title of the tool, used by the button label
 *
 * @return {jQuery|string|OO.ui.HtmlSnippet|Function} Tool title
 */
ve.ui.TableLineContextItem.prototype.getTitle = function () {
	return this.constructor.static.title;
};

/**
 * @inheritdoc
 */
ve.ui.TableLineContextItem.prototype.setup = function () {
	// Parent method
	ve.ui.TableLineContextItem.super.prototype.setup.call( this );

	this.actionButton
		.setIcon( this.constructor.static.icon )
		.setLabel( this.getTitle() );
};

/* Specific tools */

( function () {

	var className,
		modes = [ 'row', 'col' ],
		sides = [ 'before', 'after' ],
		modeNames = { row: 'Row', col: 'Column' },
		sideNames = { before: 'Before', after: 'After' };

	modes.forEach( function ( mode ) {
		var modeName = modeNames[ mode ];

		sides.forEach( function ( side ) {
			var sideName = sideNames[ side ];

			// Classes created here:
			// * ve.ui.InsertColumnBeforeContextItem
			// * ve.ui.InsertColumnAfterContextItem
			// * ve.ui.InsertRowBeforeContextItem
			// * ve.ui.InsertRowAfterContextItem
			className = 'Insert' + modeName + sideName + 'ContextItem';
			ve.ui[ className ] = function VeUiInsertRowOrColumnContextItem() {
				ve.ui.TableLineContextItem.apply( this, arguments );
			};
			OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
			ve.ui[ className ].static.name = 'insert' + modeName + sideName;
			ve.ui[ className ].static.group = 'table-' + mode;
			ve.ui[ className ].static.icon = 'tableAdd' + modeName + sideName;
			// Messages used here:
			// * visualeditor-table-insert-col-before
			// * visualeditor-table-insert-col-after
			// * visualeditor-table-insert-row-before
			// * visualeditor-table-insert-row-after
			ve.ui[ className ].static.title =
				OO.ui.deferMsg( 'visualeditor-table-insert-' + mode + '-' + side );
			ve.ui[ className ].static.commandName = 'insert' + modeName + sideName;
			ve.ui.contextItemFactory.register( ve.ui[ className ] );

			// Classes created here:
			// * ve.ui.MoveColumnBeforeContextItem
			// * ve.ui.MoveColumnAfterContextItem
			// * ve.ui.MoveRowBeforeContextItem
			// * ve.ui.MoveRowAfterContextItem
			className = 'Move' + modeName + sideName + 'ContextItem';
			ve.ui[ className ] = function VeUiMoveRowOrColumnContextItem() {
				ve.ui.TableLineContextItem.apply( this, arguments );
			};
			OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
			ve.ui[ className ].static.name = 'move' + modeName + sideName;
			ve.ui[ className ].static.group = 'table-' + mode;
			ve.ui[ className ].static.icon = 'tableMove' + modeName + sideName;
			// Messages used here:
			// * visualeditor-table-move-col-before
			// * visualeditor-table-move-col-after
			// * visualeditor-table-move-row-before
			// * visualeditor-table-move-row-after
			ve.ui[ className ].static.title =
				OO.ui.deferMsg( 'visualeditor-table-move-' + mode + '-' + side );
			ve.ui[ className ].static.commandName = 'move' + modeName + sideName;
			ve.ui[ className ].prototype.setup = function () {
				var selection, documentModel, matrix;

				// Parent method
				ve.ui.TableLineContextItem.prototype.setup.call( this );

				selection = this.context.getSurface().getModel().getSelection();
				documentModel = this.context.getSurface().getModel().getDocument();

				if ( !( selection instanceof ve.dm.TableSelection ) ) {
					this.actionButton.setDisabled( true );
					return;
				}

				if ( side === 'before' ) {
					this.actionButton.setDisabled(
						( mode === 'row' && selection.startRow === 0 ) ||
						( mode === 'col' && selection.startCol === 0 )
					);
				} else {
					matrix = selection.getTableNode( documentModel ).getMatrix();
					this.actionButton.setDisabled(
						( mode === 'row' && selection.endRow === matrix.getRowCount() - 1 ) ||
						( mode === 'col' && selection.endCol === matrix.getMaxColCount() - 1 )
					);
				}
			};
			ve.ui.contextItemFactory.register( ve.ui[ className ] );
		} );

		// Classes created here:
		// * ve.ui.DeleteColumnContextItem
		// * ve.ui.DeleteRowContextItem
		className = 'Delete' + modeName + 'ContextItem';
		ve.ui[ className ] = function VeUiDeleteRowOrColumnContextItem() {
			ve.ui.TableLineContextItem.apply( this, arguments );

			this.actionButton.setFlags( { destructive: true } );
		};
		OO.inheritClass( ve.ui[ className ], ve.ui.TableLineContextItem );
		ve.ui[ className ].static.name = 'delete' + modeName;
		ve.ui[ className ].static.group = 'table-' + mode;
		ve.ui[ className ].static.icon = 'trash';
		ve.ui[ className ].static.commandName = 'delete' + modeName;
		ve.ui[ className ].prototype.getTitle = function () {
			var count,
				selection = this.context.getSurface().getModel().getSelection();

			if ( !( selection instanceof ve.dm.TableSelection ) ) {
				count = 0;
			} else if ( mode === 'row' ) {
				count = selection.getRowCount();
			} else {
				count = selection.getColCount();
			}

			// Messages used here:
			// * visualeditor-table-delete-col
			// * visualeditor-table-delete-row
			return ve.msg( 'visualeditor-table-delete-' + mode, count );
		};
		ve.ui.contextItemFactory.register( ve.ui[ className ] );

	} );

}() );
