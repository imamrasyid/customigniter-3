<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

An uncaught Exception was encountered

Type:        <?php echo get_class($exception), "\n"; ?>
Message:     <?php echo $message, "\n"; ?>
Filename:    <?php echo $exception->getFile(), "\n"; ?>
Line Number: <?php echo $exception->getLine(), "\n"; ?>

<?php if (($previous = $exception->getPrevious()) !== NULL): ?>
Chained exceptions:
<?php
	$depth = 0;
	while ($previous !== NULL AND $depth < 10)
	{
		echo str_repeat('  ', $depth), "-> ", get_class($previous), ': ', $previous->getMessage(), "\n";
		echo str_repeat('  ', $depth), "   ", $previous->getFile(), ':', $previous->getLine(), "\n";
		$previous = $previous->getPrevious();
		$depth++;
	}
?>
<?php endif ?>

<?php $show_trace = (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE)
	OR (defined('ENVIRONMENT') && ENVIRONMENT === 'development'); ?>

<?php if ($show_trace): ?>

Backtrace:
<?php	foreach ($exception->getTrace() as $error): ?>
<?php		if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>
	File: <?php echo $error['file'], "\n"; ?>
	Line: <?php echo $error['line'] ?? 0, "\n"; ?>
	Function: <?php echo $error['function'] ?? '', "\n\n"; ?>
<?php		endif ?>
<?php	endforeach ?>

<?php endif ?>
