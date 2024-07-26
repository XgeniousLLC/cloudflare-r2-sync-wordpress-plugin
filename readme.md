# Cloudflare R2 Sync for WordPress

Cloudflare R2 Sync is a WordPress plugin that synchronizes your media library with Cloudflare R2 storage, providing a cost-effective and efficient solution for managing your website's assets.

## Why Use Cloudflare R2 Sync?

- **Cost-Effective Storage**: Cloudflare R2 offers competitive pricing compared to other cloud storage solutions.
- **Improved Performance**: Serve your media files from Cloudflare's global network, reducing load times for your visitors.
- **Scalability**: Easily handle growing media libraries without straining your WordPress server.
- **Backup**: Keep a copy of your media files in the cloud, adding an extra layer of security.

## Benefits

1. **Reduced Server Load**: Offload media serving to Cloudflare, freeing up your server resources.
2. **Global CDN**: Leverage Cloudflare's worldwide network to serve your content faster to users around the globe.
3. **Easy Management**: Sync your entire media library with a single click.
4. **Background Processing**: Large media libraries are synced in the background, ensuring your WordPress admin remains responsive.
5. **Detailed Logging**: Keep track of the sync process with comprehensive logs.

## Installation

1. Download the plugin ZIP file from the GitHub repository.
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click on the "Upload Plugin" button at the top of the page.
5. Choose the downloaded ZIP file and click "Install Now".
6. After installation, click "Activate" to enable the plugin.

## Configuration

1. In your WordPress admin panel, go to Settings > Cloudflare R2 Sync.
2. Enter your Cloudflare R2 credentials:
    - Account ID
    - Access Key ID
    - Secret Access Key
    - Bucket Name
3. Click "Save Changes" to store your settings.

## How to Use

1. After configuring your Cloudflare R2 credentials, navigate to Media > Cloudflare R2 Sync in your WordPress admin panel.
2. You'll see the following options:
    - **Sync Existing Files**: Click this button to start syncing your existing media library to Cloudflare R2.
    - **Cancel Background Jobs**: Use this to stop the sync process if needed.
    - **Clear All Logs**: Remove all sync logs if you want to start fresh.
    - **Remove All R2 Files**: Delete all files from your Cloudflare R2 bucket (use with caution).
3. The sync process will run in the background. You can monitor its progress on the same page.
4. Once synced, your media files will be served from Cloudflare R2 instead of your server.

## Troubleshooting

- If you encounter any issues, check the sync logs displayed on the Cloudflare R2 Sync page in your WordPress admin panel.
- Ensure your Cloudflare R2 credentials are correct and that your bucket has the necessary permissions set.
- For large media libraries, the sync process may take some time. Allow it to complete before making any changes.

## Support

If you encounter any bugs or have feature requests, please open an issue on our GitHub repository.

## Contributing

We welcome contributions to improve the Cloudflare R2 Sync plugin. Please fork the repository, make your changes, and submit a pull request.

## License

This plugin is licensed under the GPL v2 or later.

---

Thank you for using Cloudflare R2 Sync for WordPress! We hope it helps improve your website's performance and simplifies your media management.