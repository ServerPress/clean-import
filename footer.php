<footer class="footer">
	<div class="container">
		<p class="text-muted">You are running DesktopServer <?php echo $ds_runtime->preferences->edition; ?> edition version <?php echo $ds_runtime->preferences->version; ?></p>
		<?php $ds_runtime->do_action("ds_footer"); ?>
	</div>
</footer>


<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="http://localhost/js/jquery.min.js"></script>
<script src="http://localhost/js/bootstrap.min.js"></script>
<script src="http://localhost/js/jquery.tablesorter.js"></script>
<script>
	(function($){
		$(function(){
//			$('.detail').css({height:( $(window).height() -308)});
			console.log('removing...');
//			$('footer').remove();
//			$('#contextual-help-button').click(function() { contextual_help_button(); } );
		});
	})(jQuery);
	function contextual_help_button()
	{
		var text = $('#contextual-help-button').text();
console.log('contextual_help_button() clicked - ' + text);
		if ( 'Help v' === text ) {
			$('#contextual-help-button').text('Help ^');
			$('#contextual-help').show();
		} else {
			$('#contextual-help-button').text('Help v');
			$('#contextual-help').hide();
		}
	}
</script>
</body>
</html>