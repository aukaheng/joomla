<?php

use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

?>

<form action="<?php echo Route::_('index.php?option=com_backenddemo&view=forms'); ?>" method="post" name="adminForm" id="adminForm">
</form>

<script>
    Joomla.submitbutton = (task) => {

        Joomla.submitform(task);
    };
</script>
