<?php
namespace Xgenious\CloudflareR2Sync\Admin\Pages;

class Dashboard
{
    public function display() {
        ?>
        <div class="wrap">
            <h1>R2 Sync Dashboard</h1>
            <p>Welcome to the R2 Sync dashboard. Use the submenus to access settings and view sync logs.</p>
            <!-- Add any overview information or quick actions here -->
        </div>
        <?php
    }

    public static function render_page(){
        return (new self)->display();
    }
}