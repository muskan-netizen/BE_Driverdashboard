<?php

namespace App\Http\Controllers;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        // Validate the uploaded image
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        // Get the uploaded image file
        $imageFile = $request->file('image');
        
        // Create a BlobRestProxy client
        $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');
        $blobClient = BlobRestProxy::createBlobService($connectionString);
 
        // Upload the image to Azure Blob Storage
        $containerName = 'default'; // Replace with your Azure Blob Storage container name
        $imageName = time() . '_' . $imageFile->getClientOriginalName(); // Use original image name as blob name
        $imageContents = fopen($imageFile->getRealPath(), "r");
        // Set options for the upload
        $options = new CreateBlockBlobOptions();
        $options->setContentDisposition('inline'); 
        $blobClient->createBlockBlob($containerName, $imageName, $imageContents,$options);

        // Generate URL for accessing the uploaded image
        $imageUrl = $blobClient->getBlobUrl($containerName, $imageName);

        // You can save the image URL to the database or perform any other action as needed
        
        // Redirect back with success message

        pr($imageUrl);
        return back()->with('success', 'Image uploaded successfully. <a href="' . $imageUrl . '">View Image</a>');
    }


    public function deleteImage($imageName)
    {
        
        $containerName = 'default';
        $imageName = "1715066848_Sample-PNG-Image.png";
        $deleted = $this->deleteImageFromAzureBlob($containerName, $imageName);
        if ($deleted) {
            return response()->json(['message' => 'Image deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to delete image'], 404);
        }
    }

    
function deleteImageFromAzureBlob($containerName, $imageName)
{
   
    // Set your Azure Storage connection string
    $connectionString = env('AZURE_STORAGE_CONNECTION_STRING');

    // Create BlobRestProxy
    $blobRestProxy = BlobRestProxy::createBlobService($connectionString);
     
    try {
        // Check if the blob exists
        
        // If the blob exists, delete it
        if ($blobRestProxy) {
            $blobRestProxy->deleteBlob($containerName, $imageName);
            return true; // Return true if the blob was successfully deleted
        } else {
            return false; // Return false if the blob does not exist
        }
    } catch (\Exception $e) {

        return false; // Return false if an error occurred
    }
}

}
