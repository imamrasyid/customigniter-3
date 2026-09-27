<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div style="border:1px solid #dd4814;padding-left:20px;margin:10px 0;">

	<h4>An uncaught Exception was encountered</h4>

	<p>Type: <?php echo html_escape(get_class($exception)); ?></p>
	<p>Message: <?php echo html_escape($message); ?></p>
	<p>Filename: <?php echo html_escape($exception->getFile()); ?></p>
	<p>Line Number: <?php echo (int) $exception->getLine(); ?></p>

	<?php if (($previous = $exception->getPrevious()) !== NULL): ?>

		<p>Chained exceptions:</p>
		<?php $depth = 0; ?>
		<?php while ($previous !== NULL AND $depth < 10): ?>

			<p style="margin-left:10px">
			<?php echo str_repeat('&rarr; ', $depth); ?>
			<?php echo html_escape(get_class($previous).': '.$previous->getMessage()); ?><br />
			File: <?php echo html_escape($previous->getFile()); ?><br />
			Line: <?php echo (int) $previous->getLine(); ?>
			</p>
			<?php $previous = $previous->getPrevious(); $depth++; ?>

		<?php endwhile ?>

	<?php endif ?>

	<?php $show_trace = (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE)
		OR (defined('ENVIRONMENT') && ENVIRONMENT === 'development'); ?>

	<?php if ($show_trace): ?>

		<p>Backtrace:</p>
		<?php foreach ($exception->getTrace() as $error): ?>

			<?php if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>

				<p style="margin-left:10px">
				File: <?php echo html_escape($error['file']); ?><br />
				Line: <?php echo isset($error['line']) ? (int) $error['line'] : 0; ?><br />
				Function: <?php echo html_escape($error['function'] ?? ''); ?>
				</p>
			<?php endif ?>

		<?php endforeach ?>

	<?php endif ?>

</div>
