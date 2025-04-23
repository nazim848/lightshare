<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Lightshare\LS_Options;

$options = LS_Options::get_options();
$default_active_networks = array('facebook', 'twitter', 'linkedin', 'copy');

// Use default networks only if no settings are saved
$social_networks = isset($options['share']['social_networks']) ? $options['share']['social_networks'] : $default_active_networks;
$social_networks = is_array($social_networks) ? $social_networks : array();

// Define all available networks and their labels and icons
$available_networks = array(
    'facebook' => array(
        'label' => 'Facebook',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="m279.14 288 14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>'
    ),
    'twitter' => array(
        'label' => 'X',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8l164.9-188.5L26.8 48h145.6l100.5 132.9zm-24.8 373.8h39.1L151.1 88h-42z"/></svg>'
    ),
    'linkedin' => array(
        'label' => 'LinkedIn',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3M447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>'
    ),
    'copy' => array(
        'label' => 'Copy',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M320 448v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24V120c0-13.255 10.745-24 24-24h72v296c0 30.879 25.121 56 56 56zm0-344V0H152c-13.255 0-24 10.745-24 24v368c0 13.255 10.745 24 24 24h272c13.255 0 24-10.745 24-24V128H344c-13.2 0-24-10.8-24-24m120.971-31.029L375.029 7.029A24 24 0 0 0 358.059 0H352v96h96v-6.059a24 24 0 0 0-7.029-16.97"/></svg>'
    ),
    'pinterest' => array(
        'label' => 'Pinterest',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M204 6.5C101.4 6.5 0 74.9 0 185.6 0 256 39.6 296 63.6 296c9.9 0 15.6-27.6 15.6-35.4 0-9.3-23.7-29.1-23.7-67.8 0-80.4 61.2-137.4 140.4-137.4 68.1 0 118.5 38.7 118.5 109.8 0 53.1-21.3 152.7-90.3 152.7-24.9 0-46.2-18-46.2-43.8 0-37.8 26.4-74.4 26.4-113.4 0-66.2-93.9-54.2-93.9 25.8 0 16.8 2.1 35.4 9.6 50.7-13.8 59.4-42 147.9-42 209.1 0 18.9 2.7 37.5 4.5 56.4 3.4 3.8 1.7 3.4 6.9 1.5 50.4-69 48.6-82.5 71.4-172.8 12.3 23.4 44.1 36 69.3 36 106.2 0 153.9-103.5 153.9-196.8C384 71.3 298.2 6.5 204 6.5"/></svg>'
    ),
    'bluesky' => array(
        'label' => 'BlueSky',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M407.8 294.7c-3.3-.4-6.7-.8-10-1.3 3.4.4 6.7.9 10 1.3M288 227.1c-26.1-50.7-97.1-145.2-163.1-191.8C61.6-9.4 37.5-1.7 21.6 5.5 3.3 13.8 0 41.9 0 58.4S9.1 194 15 213.9c19.5 65.7 89.1 87.9 153.2 80.7 3.3-.5 6.6-.9 10-1.4-3.3.5-6.6 1-10 1.4-93.9 14-177.3 48.2-67.9 169.9C220.6 589.1 265.1 437.8 288 361.1c22.9 76.7 49.2 222.5 185.6 103.4 102.4-103.4 28.1-156-65.8-169.9-3.3-.4-6.7-.8-10-1.3 3.4.4 6.7.9 10 1.3 64.1 7.1 133.6-15.1 153.2-80.7C566.9 194 576 75 576 58.4s-3.3-44.7-21.6-52.9c-15.8-7.1-40-14.9-103.2 29.8C385.1 81.9 314.1 176.4 288 227.1"/></svg>'
    ),
    'whatsapp' => array(
        'label' => 'WhatsApp',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157m-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1s56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6m101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8s-14.3 18-17.6 21.8c-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.7 22.6 57.4c2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4s4.6-24.1 3.2-26.4c-1.3-2.5-5-3.9-10.5-6.6"/></svg>'
    )
);

// Create ordered array of networks based on saved order
$ordered_networks = array();
foreach ($social_networks as $network) {
    if (isset($available_networks[$network])) {
        $ordered_networks[$network] = $available_networks[$network];
        unset($available_networks[$network]);
    }
}
// Add remaining networks at the end
$ordered_networks += $available_networks;
?>
<div id="<?php echo esc_attr($tab_id); ?>" class="tab-pane">
    <h2 class="content-title"> <span class="dashicons dashicons-share"></span> Share Button</h2>
    <div class="lightshare-card">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <div class="lightshare-title-wrapper">Social Networks
                        <span class="dashicons dashicons-editor-help" data-title="Choose which social share buttons to display when the share button is clicked. Click on a square to enable or disable that specific network. Drag and drop squares to arrange the order in which they will display."></span>
                    </div>
                </th>
                <td>
                    <ul class="lightshare-social-networks">
                        <?php foreach ($ordered_networks as $network => $data) :
                            $is_active = in_array($network, $social_networks);
                        ?>
                            <li class="lightshare-social-network-<?php echo esc_attr($network); ?> <?php echo $is_active ? 'active' : ''; ?>" data-network="<?php echo esc_attr($network); ?>">
                                <label for="lightshare-share-social-network-input-<?php echo esc_attr($network); ?>" class="<?php echo $is_active ? 'active' : ''; ?>">
                                    <?php echo $data['icon']; ?>
                                    <?php echo esc_html($data['label']); ?>
                                    <input type="checkbox"
                                        id="lightshare-share-social-network-input-<?php echo esc_attr($network); ?>"
                                        name="lightshare_options[share][social_networks][]"
                                        value="<?php echo esc_attr($network); ?>"
                                        <?php checked($is_active); ?>>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <input type="hidden" id="lightshare_social_networks_order" name="lightshare_options[share][social_networks_order]" value="<?php echo esc_attr(json_encode(array_keys($ordered_networks))); ?>">
                </td>
            </tr>

        </table>
    </div>
</div>