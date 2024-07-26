<?php
namespace Xgenious\CloudflareR2Sync\Admin\Pages;

use Xgenious\CloudflareR2Sync\Sync\Synchronizer;

class SyncPage {


    public function display() {

//         $sync = new Synchronizer();
//        $url= 'https://docs.xgenious.com/wp-content/uploads/2024/01/image-29.png';
//        $attachmentId = 17767;
//       echo  $sync->downloadFile($attachmentId, $url);
        ?>
        <div class="wrap">
            <div class="heading_wrap">
                <h1>Cloudflare R2 Sync Logs</h1>
                <div class="sync-actions">
                    <button id="sync-existing-files" class="button button-primary">Sync Existing Files</button>
                    <button id="cancel-sync-jobs" class="button button-secondary">Cancel Background Jobs</button>
                    <button id="clear-sync-logs" class="button button-secondary">Clear All Logs</button>
                    <button id="remove-r2-files" class="button button-danger">Remove All R2 Files</button>
                </div>
            </div>
            <div id="sync-progress">
                <span id="sync-status"></span>
            </div>
            <div id="job-stats">
                <p>Pending To Sync: <span id="pending-jobs">0</span></p>
                <p>Synced To CloudFlare R2: <span id="processed-jobs">0</span></p>
            </div>


            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>File Name</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Timestamp</th>
                </tr>
                </thead>
                <tbody>
                <?php $this->display_log_entries(); ?>
                </tbody>
            </table>
        </div>

        <script>
            jQuery(document).ready(function($) {

                function checkSyncStatus() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'check_sync_status'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#pending-jobs').text(response.data.pending);
                                $('#processed-jobs').text(response.data.processed);
                                $('#total-jobs').text(response.data.total);
                                if (response.data.is_processing) {
                                    $('#sync-status').text('Sync in progress...');
                                    setTimeout(checkSyncStatus, 5000); // Check again in 5 seconds
                                } else {
                                    $('#sync-status').text('Sync completed');
                                }
                            }
                        }
                    });
                }


                function updateJobStats() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_job_stats'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#pending-jobs').text(response.data.pending);
                                $('#processed-jobs').text(response.data.processed);
                            }
                        }
                    });
                }

                // Update job stats every 5 seconds
                setInterval(updateJobStats, 5000);

                $('#sync-existing-files').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true);
                    $('#sync-status').text('');
                    checkSyncStatus();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sync_existing_files'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#sync-status').text('Sync process started in the background.');
                                updateJobStats();
                            } else {
                                $('#sync-status').text('Failed to start sync process.');
                            }
                        },
                        error: function() {
                            $('#sync-status').text('An error occurred while starting the sync process.');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                });

                $('#cancel-sync-jobs').on('click', function() {
                    var button = $(this);
                    button.prop('disabled', true);
                    $('#sync-status').text('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cancel_sync_jobs'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#sync-status').text('Background jobs cancelled successfully.');
                                updateJobStats();
                            } else {
                                $('#sync-status').text('Failed to cancel background jobs.');
                            }
                        },
                        error: function() {
                            $('#sync-status').text('An error occurred while cancelling background jobs.');
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                });

                // Initial update of job stats
                updateJobStats();

                $('#clear-sync-logs').on('click', function() {
                    if (confirm('Are you sure you want to clear all sync logs? This action cannot be undone.')) {
                        var button = $(this);
                        button.prop('disabled', true);
                        $('#sync-status').text('');
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'clear_sync_logs'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#sync-status').text('All sync logs cleared successfully.');
                                    $('table tbody').html('<tr><td colspan="5">No sync logs found.</td></tr>');
                                } else {
                                    $('#sync-status').text('Failed to clear sync logs.');
                                }
                            },
                            error: function() {
                                $('#sync-status').text('An error occurred while clearing sync logs.');
                            },
                            complete: function() {
                                button.prop('disabled', false);
                            }
                        });
                    }
                });

                $('#remove-r2-files').on('click', function() {
                    if (confirm('Are you sure you want to remove all files from Cloudflare R2? This action cannot be undone.')) {
                        var button = $(this);
                        button.prop('disabled', true);
                        $('#sync-status').text('');
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'remove_all_r2_files'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#sync-status').text('All files removed from Cloudflare R2 successfully.');
                                } else {
                                    $('#sync-status').text('Failed to remove files from Cloudflare R2: ' + response.data);
                                }
                            },
                            error: function() {
                                $('#sync-status').text('An error occurred while removing files from Cloudflare R2.');
                            },
                            complete: function() {
                                button.prop('disabled', false);
                            }
                        });
                    }
                });
            });
        </script>
        <?php
    }

    private function display_log_entries()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'xg_cloudflare_r2_sync_log';

        $per_page = 50;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        if ($logs) {
            foreach ($logs as $log) {
                $attachment = get_post($log->file_id);
                $file_name = $attachment ? $attachment->post_title : 'Unknown';
                ?>
                <tr>
                    <td><?php echo esc_html($log->file_id); ?></td>
                    <td><?php echo esc_html($file_name); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                    <td><?php echo esc_html($log->message); ?></td>
                    <td><?php echo esc_html($log->timestamp); ?></td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="5">No sync logs found.</td></tr>';
        }

        // Add pagination
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_logs / $per_page);

        if ($total_pages > 1) {
            echo '<tr><td colspan="5">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));
            echo '</td></tr>';
        }
    }

    public static function render_page() {
        return (new self)->display();
    }
}