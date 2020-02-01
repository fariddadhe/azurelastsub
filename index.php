    
    <?php

    require_once 'vendor/autoload.php';
    require_once "./random_string.php";

    use MicrosoftAzure\Storage\Blob\BlobRestProxy;
    use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
    use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
    use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
    use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
    function getData(){
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');

        // Create blob client.
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        // $fileToUpload = "kucing.jpg";
        // $fileToUpload = $_POST['file'];

        $url = "";

        if (!isset($_GET["Cleanup"])) {
            // Create container options object.
            $createContainerOptions = new CreateContainerOptions();

            // Set public access policy. Possible values are
            // PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
            // CONTAINER_AND_BLOBS:
            // Specifies full public read access for container and blob data.
            // proxys can enumerate blobs within the container via anonymous
            // request, but cannot enumerate containers within the storage account.
            //
            // BLOBS_ONLY:
            // Specifies public read access for blobs. Blob data within this
            // container can be read via anonymous request, but container data is not
            // available. proxys cannot enumerate blobs within the container via
            // anonymous request.
            // If this value is not specified in the request, container data is
            // private to the account owner.
            $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

            // Set container metadata.
            $createContainerOptions->addMetaData("key1", "value1");
            $createContainerOptions->addMetaData("key2", "value2");

            $containerName = "blockblobs".generateRandomString();

            $fileToUpload = "kucing.jpg";
            try {
                
                // Create container.
                $blobClient->createContainer($containerName, $createContainerOptions);

                // Getting local file so that we can upload it to Azure
                $myfile = fopen($fileToUpload, "r") or die("Unable to open file!");
                fclose($myfile);
                
                # Upload file as a block blob
                echo "Uploading BlockBlob: ".PHP_EOL;
                echo $fileToUpload;
                echo "<br />";
                
                $content = fopen($fileToUpload, "r");

                //Upload blob
                $blobClient->createBlockBlob($containerName, $fileToUpload, $content);

                // List blobs.
                $listBlobsOptions = new ListBlobsOptions();
                $listBlobsOptions->setPrefix("kucing");

                echo "These are the blobs present in the container: ";

                do{
                    $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                    foreach ($result->getBlobs() as $blob)
                    {
                        echo $blob->getName().": ".$blob->getUrl()."<br />";
                        $url = $blob->geturl();
                    }
                
                    $listBlobsOptions->setContinuationToken($result->getContinuationToken());
                } while($result->getContinuationToken());
                echo "<br />";

                // Get blob.
                // echo "This is the content of the blob uploaded: ";
                // $blob = $blobClient->getBlob($containerName, $fileToUpload);
                // fpassthru($blob->getContentStream());
                // echo "<br />";
            }
            catch(ServiceException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179439.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
            catch(InvalidArgumentTypeException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179439.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
        } 
        else 
        {

            try{
                // Delete container.
                echo "Deleting Container".PHP_EOL;
                echo $_GET["containerName"].PHP_EOL;
                echo "<br />";
                $blobClient->deleteContainer($_GET["containerName"]);
            }
            catch(ServiceException $e){
                // Handle exception based on error codes and messages.
                // Error codes and messages are here:
                // http://msdn.microsoft.com/library/azure/dd179439.aspx
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
        }
        return $url;
    }

    if(isset($_POST['file'])){
        // $file = $_FILES['file']['tmp_name'];
							
		// $path_parts = pathinfo(($_FILES['file']['name']));
							
		// $extension = $path_parts['extension'];
							
		// $image = round(microtime(true) * 1000) . '.' . $extension;
		// $filedest = dirname(__FILE__) . '/img/' . $image;
        // $path = "./img/";
        // $add = move_uploaded_file($file, $path.$image);
        // $result = getData($path.$image);
        $result = getData();
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Analyze Sample</title>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    </head>

    

    <script type="text/javascript">
        function processImage() {
            //sdfsdf
            // **********************************************
            // *** Update or verify the following values. ***
            // **********************************************
     
            // Replace <Subscription Key> with your valid subscription key.
            var subscriptionKey = "b696bf5121d04757a2063556aa701083";
     
            // You must use the same Azure region in your REST API method as you used to
            // get your subscription keys. For example, if you got your subscription keys
            // from the West US region, replace "westcentralus" in the URL
            // below with "westus".
            //
            // Free trial subscription keys are generated in the "westus" region.
            // If you use a free trial subscription key, you shouldn't need to change
            // this region.
            var uriBase =
                "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
     
            // Request parameters.
            var params = {
                "visualFeatures": "Categories,Description,Color",
                "details": "",
                "language": "en",
            };
     
            // Display the image.
            var sourceImageUrl = document.getElementById("inputImage").value;
            document.querySelector("#sourceImage").src = sourceImageUrl;
     
            // Make the REST API call.
            $.ajax({
                url: uriBase + "?" + $.param(params),
     
                // Request headers.
                beforeSend: function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader(
                        "Ocp-Apim-Subscription-Key", subscriptionKey);
                },
     
                type: "POST",
     
                // Request body.
                data: '{"url": ' + '"' + sourceImageUrl + '"}',
            })
     
            .done(function(data) {
                // Show formatted JSON on webpage.
                $("#responseTextArea").val(JSON.stringify(data, null, 2));
            })
     
            .fail(function(jqXHR, textStatus, errorThrown) {
                // Display error message.
                var errorString = (errorThrown === "") ? "Error. " :
                    errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" :
                    jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });
        };
    </script>
    <body onload="processImage()">
    <div id="process"></div>   


    <?php if (isset($result)) { ?>
        <h1> Result: <?php echo $result ?></h1>
    <?php } ?>
    <form action="" method="post">
        <div>
            <label for="file">Choose file to analyze</label>
            <input type="file" id="file" name="file" multiple>
        </div>
        <div>
            <button>Submit</button>
        </div>
    </form>
   
    
    
     
    <h1>Analyze image:</h1>
    Enter the URL to an image, then click the <strong>Analyze image</strong> button.
    <br><br>
    Image to analyze:sdfsdf
    <input type="text" name="inputImage" id="inputImage"
        value="http://upload.wikimedia.org/wikipedia/commons/3/3c/Shaki_waterfall.jpg" />
    <button onclick="processImage()">Analyze image</button>
    <br><br>
    <div id="wrapper" style="width:1020px; display:table;">
        <div id="jsonOutput" style="width:600px; display:table-cell;">
            Response:
            <br><br>
            <textarea id="responseTextArea" class="UIInput"
                      style="width:580px; height:400px;"></textarea>
        </div>
        <div id="imageDiv" style="width:420px; display:table-cell;">
            Source image:
            <br><br>
            <img id="sourceImage" width="400" />
        </div>
    </div>
    </body>
    </html>