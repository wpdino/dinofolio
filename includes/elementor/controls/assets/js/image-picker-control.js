var WPDINOControlBaseDataView = elementor.modules.controls.BaseData.extend({
    onReady: function () {

			this.ui.select.wpdinoImagepicker({
				show_label: true
			});
			this.ui.select.on('change', () => {
				this.saveValue();
			} )
    },
	saveValue: function() {
        this.setValue( this.ui.select.val() );
    },
    onBeforeDestroy: function () {
		//this.saveValue();
        this.ui.select.wpdinoImagepicker( 'destroy' );
    }
});

elementor.addControlView( 'wpdino_select_image', WPDINOControlBaseDataView );