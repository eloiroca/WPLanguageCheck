<?php
/**
 * Template para el modal de sugerencia de idioma
 */
?>
<div class="modal micromodal-slide" id="modal-wplanguagecheck" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1">
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="wplanguagecheck-title">
            <header class="modal__header">
                <h2 class="modal__title" id="wplanguagecheck-title"><?php echo __wpl('Location confirmation title'); ?></h2>
                <button type="button" id="wplanguagecheck-close" class="modal__close" data-micromodal-close aria-label="<?php echo esc_attr(__wpl('Close')); ?>"></button>
            </header>
            <main class="modal__content" id="wplanguagecheck-content">
                <div class="wplanguagecheck__actions">
                </div>
            </main>
        </div>
    </div>
</div>
