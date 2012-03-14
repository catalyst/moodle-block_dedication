<?php
//
// Capability definitions for the dedication block.
//

$block_dedication_capabilities = array(

    'block/dedication:use' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

);

?>
