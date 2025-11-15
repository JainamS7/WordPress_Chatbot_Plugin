<?php
/**
 * JSON Format Verification
 * 
 * This script shows the difference between array() and stdClass()
 * for ZeroEntropy API requests.
 */

echo "ZeroEntropy API JSON Format Verification\n";
echo str_repeat("=", 50) . "\n\n";

echo "❌ WRONG - Empty array (causes HTTP 422):\n";
echo json_encode(array()) . "\n\n";

echo "✅ CORRECT - Empty object:\n";
echo json_encode(new stdClass()) . "\n\n";

echo "✅ CORRECT - Object with collection_name:\n";
echo json_encode(array('collection_name' => 'wordpress_posts')) . "\n\n";

echo "✅ CORRECT - Status request body:\n";
echo json_encode(new stdClass()) . "\n\n";

echo "✅ CORRECT - Collection list request body:\n";
echo json_encode(new stdClass()) . "\n\n";

echo "✅ CORRECT - Add document request body:\n";
$document_request = array(
    'collection_name' => 'wordpress_posts',
    'path' => 'post_123',
    'content' => array(
        'type' => 'text',
        'text' => 'Sample content'
    ),
    'metadata' => array(
        'title' => 'Sample Post',
        'author' => 'Test Author'
    )
);
echo json_encode($document_request, JSON_PRETTY_PRINT) . "\n\n";

echo "The key difference:\n";
echo "- array() produces: [] (array)\n";
echo "- new stdClass() produces: {} (object)\n";
echo "- ZeroEntropy API expects objects, not arrays\n";
