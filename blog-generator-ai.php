<?php
/*
Plugin Name: Blog Generator AI
Description: Generates blog post titles and content using ChatGPT.
Version: 1.0
Author: John Mark Causing
Date: May 1, 2023
*/


// Register the plugin settings menu
add_action('admin_menu', 'blog_generator_ai_settings_menu');
function blog_generator_ai_settings_menu() {
    add_options_page(
        'Blog Generator AI Settings',
        'Blog Generator AI',
        'manage_options',
        'blog-generator-ai-settings',
        'blog_generator_ai_settings_page'
    );
}

// Register the plugin settings page content
function blog_generator_ai_settings_page() {

    // Enqueue the style.css file
    wp_enqueue_style('blog-generator-style', plugin_dir_url(__FILE__) . 'style.css');

    // Retrieve the API key from the plugin settings
    $api_key = get_option('blog_generator_api_key');

    // Retrieve the Unsplash access key from the plugin settings
    $unsplash_access_key = get_option('blog_generator_unsplash_access_key');

    ?>
    <div class="wrap">
        <h1>Blog Generator AI Settings</h1>
        <form method="post" action="options.php">
            <?php
            // Output nonce, action, and option_page fields for security
            settings_fields('blog_generator_ai_settings');
            do_settings_sections('blog-generator-ai-settings');
            ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">ChatGPT API Key:</th>
                    <td>
                        <input type="text" name="blog_generator_api_key" value="<?php echo esc_attr($api_key); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Unsplash Access Key:</th>
                    <td>
                        <input type="text" name="blog_generator_unsplash_access_key" value="<?php echo esc_attr($unsplash_access_key); ?>" />
                    </td>
                </tr>

            </table>

            <h2>Cron Schedule</h2>
            <?php
            // Get the selected cron schedule option
            $selected_schedule = get_option('blog_generator_ai_cron_schedule');

            // Output the cron schedule dropdown
            echo '<select name="blog_generator_ai_cron_schedule">';
            // Add the "Disabled" option
            echo '<option value="disabled" ' . selected($selected_schedule, 'disabled', false) . '>Disabled</option>';

            // Add the "Twice a Day" option
            echo '<option value="twice_a_day" ' . selected($selected_schedule, 'twice_a_day', false) . '>Twice a Day</option>';

            // Add the "Every Minute" option
            echo '<option value="every_minute" ' . selected($selected_schedule, 'every_minute', false) . '>Every Minute</option>';

            // Add the "Three Times a Day" option
            echo '<option value="three_times_a_day" ' . selected($selected_schedule, 'three_times_a_day', false) . '>Three Times a Day</option>';

            // Add the "Four Times a Day" option
            echo '<option value="four_times_a_day" ' . selected($selected_schedule, 'four_times_a_day', false) . '>Four Times a Day</option>';

            // CHeck this. There is a duplicate
            foreach (wp_get_schedules() as $key => $schedule) {
                $selected = ($selected_schedule == $key) ? 'selected' : '';
                echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($schedule['display']) . '</option>';
            }

            echo '</select>';
            ?>

            <?php submit_button(); ?>
        </form>

        <div id="blog-generator-test-container">
            <h2>Display Test</h2>
            <button class="button button-primary" id="blog-generator-display-test">Display Test</button>
            <p id="blog-generator-test-result-title"></p>
            <p id="blog-generator-test-result-image"></p>
            <p id="blog-generator-test-result-category"></p>
            <p id="blog-generator-test-result-content"></p>
        </div>


        <div id="loading-animation" style="display: none;">
            <div class="loading-box">
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <div id="loading-progress">Loading...</div>
            </div>
        </div>
    </div>


    <!-- This is for loading animation-->
    <script>
    
    document.getElementById('blog-generator-display-test').addEventListener('click', function () {
        var xhr = new XMLHttpRequest();
        var loadingAnimation = document.getElementById('loading-animation');
        var progressElement = document.getElementById('loading-progress');
        var titleElement = document.getElementById('blog-generator-test-result-title');
        var imageElement = document.getElementById('blog-generator-test-result-image');
        var category = document.getElementById('blog-generator-test-result-category');
        
        var contentElement = document.getElementById('blog-generator-test-result-content');
        var progressBar = document.querySelector('.progress-bar');
        var response; // Define the response variable

        xhr.open('POST', ajax_object.ajax_url);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    titleElement.textContent = response.data.title;
                    category.textContent = response.data.category;
                    imageElement.textContent = response.data.image;
                    contentElement.textContent = response.data.content;
                } else {
                    console.error('AJAX request failed.');
                }
                // Hide the loading animation and enable the button
                loadingAnimation.style.display = 'none';
                document.getElementById('blog-generator-display-test').disabled = false;
                progressBar.style.width = '100%';
                progressElement.textContent = 'Loading...';
            }
        };

        // Show the loading animation and disable the button
        loadingAnimation.style.display = 'block';
        document.getElementById('blog-generator-display-test').disabled = true;

        // Reset progress bar
        progressBar.style.width = '0%';
        progressElement.textContent = 'Loading... 0%';

        // Calculate the total number of progress steps
        var totalSteps = 20; // Adjust this value based on your desired number of steps

        // Track the current step
        var currentStep = 0;

        // Update progress bar
        var progressInterval = setInterval(function () {
            currentStep++;
            var progress = (currentStep / totalSteps) * 100;
            if (progress <= 100) {
                progressBar.style.width = progress + '%';
                progressElement.textContent = 'Loading... ' + progress.toFixed(2) + '%';
            }
        }, 800); // Change the interval duration here (e.g., 100 milliseconds)

        xhr.send('action=blog_generator_test_run');
    });

    </script>
    <!-- This is for loading animation-->

    <?php
}


// Register the plugin settings
add_action('admin_init', 'blog_generator_ai_register_settings');
function blog_generator_ai_register_settings() {
    register_setting(
        'blog_generator_ai_settings',
        'blog_generator_ai_cron_schedule',
        'blog_generator_ai_sanitize_cron_schedule',
    );

    register_setting(
        'blog_generator_ai_settings',
        'blog_generator_api_key'
    );

    register_setting(
        'blog_generator_ai_settings',
        'blog_generator_unsplash_access_key'
    );    

    // Add WP cron event if the selected schedule field is not empty
    $selected_schedule = get_option('blog_generator_ai_cron_schedule');
    if ($selected_schedule !== 'disabled') {
        add_action('init', 'blog_generator_ai_schedule_cron');
    } else {
        blog_generator_ai_remove_cron_event();
    }
}

// Update cron event when the schedule field is changed
add_action('update_option_blog_generator_ai_cron_schedule', 'blog_generator_ai_update_cron_event', 10, 2);
function blog_generator_ai_update_cron_event($old_value, $new_value) {
    // Remove previous cron event
    wp_clear_scheduled_hook('blog_generator_ai_cron_event');

    // Schedule the new cron event if the new value is not empty
    if (!empty($new_value)) {
        wp_schedule_event(time(), $new_value, 'blog_generator_ai_cron_event');
    }
}

// Sanitize the cron schedule option
function blog_generator_ai_sanitize_cron_schedule($input) {
    if ($input === 'disabled') {
        // If "Disabled" is selected, remove the cron event
        blog_generator_ai_remove_cron_event();
        return 'disabled';
    }

    return sanitize_text_field($input);
}


// Schedule the WP cron event
function blog_generator_ai_schedule_cron() {
    if (!wp_next_scheduled('blog_generator_ai_cron_event')) {
        $selected_schedule = get_option('blog_generator_ai_cron_schedule');

        // Schedule the cron event based on the selected schedule
        wp_schedule_event(time(), $selected_schedule, 'blog_generator_ai_cron_event');
    }
}

// Function to run on the scheduled WP cron event
add_action('blog_generator_ai_cron_event', 'blog_generator_test_run');

// Call the schedule function during plugin activation
register_activation_hook(__FILE__, 'blog_generator_ai_schedule_cron');


// Remove cron event when the plugin is deactivated
register_deactivation_hook(__FILE__, 'blog_generator_ai_remove_cron_event');

function blog_generator_ai_remove_cron_event() {
    wp_clear_scheduled_hook('blog_generator_ai_cron_event');
}

// Add custom cron schedules
add_filter('cron_schedules', 'blog_generator_ai_custom_cron_schedules');

function blog_generator_ai_custom_cron_schedules($schedules) {
    $schedules['twice_a_day'] = array(
        'interval' => 43200, // 12 hours in seconds
        'display'  => 'Twice a Day'
    );

    $schedules['every_minute'] = array(
        'interval' => 60, // 1 minute in seconds
        'display'  => 'Every Minute'
    );

    $schedules['three_times_a_day'] = array(
        'interval' => 28800, // 8 hours in seconds
        'display'  => 'Three Times a Day'
    );

    $schedules['four_times_a_day'] = array(
        'interval' => 21600, // 6 hours in seconds
        'display'  => 'Four Times a Day'
    );

    return $schedules;
}

// Generate post title via ChatGPT
function generate_blog_post_title($api_key)
{
    // Health keywords
    $health_keywords = array("Vitality", "Wellness practices", "Fitness regime", "Mind-body wellness", "Preventive healthcare", "Healthy lifestyle choices", "Optimal well-being", "Emotional balance", "Healthy mindset", "Holistic health", "Well-being strategies", "Personal wellness", "Healthy living tips", "Fitness routines", "Mental health", "Wellness goals", "Nutrition and health", "Self-care rituals", "Healthy habits formation", "Inner peace", "Stress management techniques", "Natural remedies", "Immune system support", "Health promotion", "Healthy aging strategies", "Resilience building exercises", "Healthy sleep patterns", "Hygiene practices", "Social well-being", "Healthy work-life balance", "Gratitude and well-being","Eating habits", "Dietary choices", "Nutritional needs", "Balanced nutrition plan", "Healthy meal options", "Diet tips", "Weight management strategies", "Food selection", "Healthy cooking methods", "Portion sizes", "Nutrient density", "Mindful food choices", "Diet modifications", "Healthy food substitutes", "Culinary techniques", "Meal prepping ideas", "Diet planning", "Nutritional supplements", "Health-conscious recipes", "Meal variety", "Macronutrient distribution", "Dietary guidelines", "Eating for energy", "Healthy digestion", "Food sensitivities", "Antioxidant-rich foods", "Whole grain options", "Organic food choices", "Superfoods", "Healthy drink alternatives", "Flavorful spices and herbs", "Probiotic-rich foods", "Diet and exercise synergy","Alternate day fasting", "Fasting windows", "Time-restricted feeding", "Extended fasting", "Fasting protocols", "Autophagy benefits", "Cognitive benefits of fasting", "Fasting for longevity", "Metabolic health benefits", "Fasting and insulin regulation", "Exercise and fasting", "Fasting and cellular repair", "Fasting and energy levels", "Fasting and mental clarity", "Fasting and weight management", "Fasting for detoxification", "Fasting for hormonal balance", "Fasting and muscle growth", "Fasting and cardiovascular health", "Fasting for metabolic flexibility", "Fasting for digestive health", "Fasting and appetite control", "Fasting adaptations for athletes", "Fasting and cognitive performance", "Fasting and inflammation reduction", "Fasting and immune function", "Fasting and blood sugar control", "Fasting and stress response", "Fasting and nutrient partitioning", "Fasting strategies for beginners","Self-care practices", "Health education", "Positive well-being", "Lifestyle choices", "Healthy mindset", "Physical fitness", "Mental wellness", "Emotional well-being", "Nutritional wellness", "Holistic approach", "Preventive care", "Health empowerment", "Natural healing", "Inner harmony", "Social support", "Personal growth", "Optimal functioning", "Healthy aging", "Energy balance", "Restorative sleep", "Mindfulness exercises", "Resilience building", "Stress reduction", "Healthy relationships", "Active living", "Environmental wellness", "Healthy habits", "Healthy workplace", "Digital well-being", "Health advocacy", "Life balance","Nutrition plan", "Healthy eating", "Dietary habits", "Nutrient-rich foods", "Wholesome meals", "Clean eating", "Balanced plate", "Nutrition guidelines", "Meal planning", "Healthy cooking", "Portion control", "Food variety", "Well-balanced diet", "Dietary restrictions", "Nutrition education", "Food groups", "Healthy snacks", "Meal ideas", "Eating patterns", "Mindful nutrition", "Nutrition goals", "Meal frequency", "Nutrition labels", "Plant-based diet", "Gut health", "Weight loss", "Nutrition science", "Food preparation", "Healthy desserts", "Dietary fiber", "Protein sources", "Vitamin-rich foods", "Hydration tips","Fasting benefits", "Hunger regulation", "Metabolic flexibility", "Body composition", "Meal timing", "Caloric restriction", "Fasting schedule", "Cellular repair", "Energy metabolism", "Fasting effects", "Insulin sensitivity", "Blood sugar management", "Fasting techniques", "Autophagy process", "Cognitive function", "Exercise performance", "Fasting adaptations", "Intermittent fasting patterns", "Fasting variations", "Hormonal balance", "Digestive health", "Fat loss", "Muscle preservation", "Fasting and longevity", "Inflammation reduction", "Immune system health", "Fasting and brain health", "Fasting and heart health", "Fasting and detoxification", "Stress resistance", "Fasting strategies");
    // Get random topics from the list
    $topic_randomWord_health = getRandomWord($health_keywords);

    // Set the temperature for the API response
    $temperature = 0.8;

    // Set the maximum number of tokens to generate
    $max_tokens = 50;

    // Set the API endpoint URL
    $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';

    // Set the headers for the API request
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    // Generate a new title if the existing title already exists in the database
    do {
        // Set the data for the API request
        $data = array(
            'prompt' => "Generate a blog post title about $topic_randomWord_health with 20 to 22 words.",
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        );

        // Initialize cURL and make the request
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($curl);
        curl_close($curl);

        // Check if the cURL request was successful
        if ($response !== false) {
            // Decode the API response
            $response_data = json_decode($response, true);

            // Check if the API response was successfully decoded
            if ($response_data !== null) {
                // Extract the generated title from the response
                $title = isset($response_data['choices'][0]['text']) ? str_replace('"', '', $response_data['choices'][0]['text']) : '';

                // Check if the generated title already exists in the database
                $title_exists = check_title_exists($title);
            }
        }
    } while ($title_exists);

    return $title;

}

// Get a random word from keywords
function getRandomWord($array) {
    $randomIndex = array_rand($array);
    return $array[$randomIndex];
}

// Function to check if title already exists in the database
function check_title_exists($title) {
    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'title' => $title
    ));

    return $query->have_posts();
}

// Helper function to generate blog post content
function generate_blog_post_content($api_key, $topic)
{

    // Set the temperature for the API response
    $temperature = 0.8;

    // Set the maximum number of tokens to generate
    $max_tokens = 2000; // Increase the max tokens to generate more content

    // Set the API endpoint URL
    $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';

    // Set the headers for the API request
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    // Set the data for the API request
    $data = array(
        'prompt' => "Write a blog post about $topic",
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    );

    // Initialize cURL and make the request
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($curl);
    curl_close($curl);

    // Check if the cURL request was successful
    if ($response === false) {
        echo json_encode(array('message' => 'cURL request failed.'));
        return '';
    }

    // Decode the API response
    $response_data = json_decode($response, true);

    // Check if the API response was successfully decoded
    if ($response_data === null) {
        echo json_encode(array('message' => 'API response decoding failed.'));
        return '';
    }

    // Extract the generated content from the response
    $choices = isset($response_data['choices']) ? $response_data['choices'] : '';
    $content = '';
    
    // Loop through each choice and concatenate the content
    foreach ($choices as $choice) {
        $text = isset($choice['text']) ? $choice['text'] : '';
        $content .= $text . "\n\n";
    }
    return $content;
    
}

// Helper function to get a random image from Unsplash
function get_random_unsplash_image($access_key, $topic_title)
{
    // Encode the search query
    $encoded_query = urlencode($topic_title);
    // Set the number of results to retrieve from Unsplash
    $per_page = 20;

    // Set the API endpoint URL
    $url = "https://api.unsplash.com/search/photos?query={$encoded_query}&per_page={$per_page}";

    // Set the cURL options
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Client-ID {$access_key}"
        )
    );

    // Initialize cURL and make the request
    $curl = curl_init($url);
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    curl_close($curl);

    // Parse the JSON response and extract the image URLs
    $json = json_decode($response, true);
    $total_results = count($json['results']);

    if ($total_results > 0) {
        // Filter landscape images
        $landscape_images = array_filter($json['results'], function ($result) {
            return $result['width'] > $result['height'];
        });

        $landscape_images = array_values($landscape_images); // Reset array keys

        $total_landscape_results = count($landscape_images);

        if ($total_landscape_results > 0) {
            // Generate a random index within the range of available landscape results
            $random_index = rand(0, $total_landscape_results - 1);
            $image_url = $landscape_images[$random_index]['urls']['regular'];

            // Download the image
            $image_data = file_get_contents($image_url);

            // Generate a unique file name for the image
            $filename = uniqid() . '.jpg';

            // Get the path to the current WordPress upload folder
            $upload_dir = wp_upload_dir();

            // Specify the full path for the image file
            $image_path = $upload_dir['path'] . '/' . $filename;

            // Save the image file to the upload folder
            file_put_contents($image_path, $image_data);

            // Get the URL for the uploaded image file
            $image_url = $upload_dir['url'] . '/' . $filename;

            return $image_url;
        } else {
            // No landscape results found
            return false;
        }
    } else {
        // No results found
        return false;
    }
}


// AJAX handler for test run
add_action('wp_ajax_blog_generator_test_run', 'blog_generator_test_run');
add_action('wp_ajax_nopriv_blog_generator_test_run', 'blog_generator_test_run');


// Main function to create a blog post. All data are gathered from ChatGTP and unsplash for the image.
function blog_generator_test_run()
{
    // Setup variables
    $api_key = get_option('blog_generator_api_key');
    $access_key = get_option('blog_generator_unsplash_access_key');
    $title = generate_blog_post_title($api_key);
    $content = generate_blog_post_content($api_key, $title);
    $image_url = get_random_unsplash_image($access_key, $title);
    $category_name = get_correct_category_name($api_key, $title);

    // Create a post using the $title, $content and the image
    $new_post = array(
        'post_title' => $title,
        'post_content' => '<img src="' . $image_url . '" />' . $content,
        'post_status' => 'publish',
        'post_author' => 2 // Change this to the user ID of the author if needed
    );

    // Insert the post into the database
    $post_id = wp_insert_post($new_post);

    // Check if the category exists
    $existing_category = get_category_by_slug($category_name);

    // If the category doesn't exist, create a new category
    if (!$existing_category) {
        $new_category = wp_insert_term($category_name, 'category');

        if (is_wp_error($new_category)) {
            http_response_code(500);
            echo json_encode(array('message' => 'Category creation failed.'));
            exit();
        }

        $new_category_id = $new_category['term_id'];
    } else {
        $new_category_id = $existing_category->term_id;
    }

    // Set the category for the blog post
    wp_set_post_categories($post_id, array($new_category_id));

	// Set the featured image for the blog post
    set_featured_image($post_id, $image_url);

    $response = array(
        'title' => $title,
        'content' => $content,
        'image' => $image_url,
        'category' => $category_name

    );

    wp_send_json_success($response);
    return $response;
}


// Helper function to generate and set the featured image for a blog post
function set_featured_image($post_id, $image_url) {
    $upload_dir = wp_upload_dir();

    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);

    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null);

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $file, $post_id);

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    set_post_thumbnail($post_id, $attach_id);

    // Debugging using error_log
    if (has_post_thumbnail($post_id)) {
        error_log('Featured image was set successfully for post ID: ' . $post_id);
    } else {
        error_log('Failed to set featured image for post ID: ' . $post_id);
        error_log('Image URL: ' . $image_url);
        error_log('File path: ' . $file);
        error_log('Attachment ID: ' . $attach_id);
    }
}

// Get the correct and appropriate category name for a given title using ChatGPT.
function get_correct_category_name($api_key, $title) {

    // Set the temperature for the API response
    $temperature = 0.8;

    // Set the maximum number of tokens to generate
    $max_tokens = 50;

    // Set the API endpoint URL
    $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';

    // Set the headers for the API request
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    );

    // Prepare the prompt for ChatGPT
    $prompt = "Given the title: '$title', please provide the correct and appropriate category name for this blog post:";

    // Generate a response from ChatGPT
    do {
        // Set the data for the API request
        $data = array(
            'prompt' => $prompt,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        );

        // Initialize cURL and make the request
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($curl);
        curl_close($curl);

        // Check if the cURL request was successful
        if ($response !== false) {
            // Decode the API response
            $response_data = json_decode($response, true);

            // Check if the API response was successfully decoded
            if ($response_data !== null) {
                // Extract the category name from the response
                $category_name = isset($response_data['choices'][0]['text']) ? trim($response_data['choices'][0]['text']) : '';

                // Validate the category name
                if (!empty($category_name)) {
                    return $category_name;
                }
            }
        }
    } while (true);

    return false;
}


// Enqueue the JavaScript file for the test run functionality
function blog_generator_enqueue_scripts()
{
    wp_enqueue_script('blog-generator-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'));
    wp_localize_script('blog-generator-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'blog_generator_enqueue_scripts');
