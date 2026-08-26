( function( $ ) {

	// ready event
	$( function() {
		// get charts
		var charts = cnDashboardArgs.charts;

		if ( Object.entries( charts ).length > 0 ) {
			for ( const [key, config] of Object.entries( charts ) ) {
				// create canvas
				var canvas = document.getElementById( 'cn-' + key + '-chart' );

				if ( canvas )
					new Chart( canvas, config );
			}
		}
	} );

} )( jQuery );