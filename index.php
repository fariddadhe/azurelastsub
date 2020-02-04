<!DOCTYPE html>
    <html>
    <head>
        <title>Analyze Sample</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    </head>

    

    
    <!-- <body onload="processImage()">
    <div id="process"></div>    -->

    <body>
    


    <?php if (isset($result)) { ?>
        <h1> Result: <?php echo $result ?></h1>
    <?php } ?>
    <form action="" method="post" enctype="multipart/form-data">
        <div>
            <label for="file">Choose file to analyze</label>
            <input type="file" id="fileToUpload" name="fileToUpload">
        </div>
        <div>
        <input type="submit" value="Upload Image" name="submit">
        </div>
    </form>
    <!-- <input type="file" id="fileToUpload" name="fileToUpload">
    <button onclick="data()">Submit</button> -->
   
    
    
  
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

<script type="text/javascript">
    
    function processImage(data) {

        var data = data;

        console.log("data :" + data);
        var subscriptionKey = "b696bf5121d04757a2063556aa701083";
 

        var uriBase =
            "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
 
        // Request parameters.
        var params = {
            "visualFeatures": "Categories,Description,Color",
            "details": "",
            "language": "en",
        };
 
        // Display the image.
        
        document.querySelector("#sourceImage").src = data;
 
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
            data: '{"url": ' + '"' + data + '"}',
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
    <?php

    require_once 'vendor/autoload.php';
    require_once "./random_string.php";

    use MicrosoftAzure\Storage\Blob\BlobRestProxy;
    use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
    use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
    use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
    use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
    function getData(){
       $connectionString = "DefaultEndpointsProtocol=https;AccountName=azurewebblob;AccountKey=IRtUInLb2Cl8bYEp54JjFOsPTd9+eNdytEprSfLVTZWQKPp6i668ez1iU3h99iZRBjpqSsxwBi4dwHv7UvHLXg==";

        // Create blob client.
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        // $fileToUpload = "kucing.jpg";
        // $fileToUpload = $_POST['file'];

        $url = "";

        if (!isset($_GET["Cleanup"])) {
            // Create container options object.
            $createContainerOptions = new CreateContainerOptions();

            $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

            // Set container metadata.
            $createContainerOptions->addMetaData("key1", "value1");
            $createContainerOptions->addMetaData("key2", "value2");

            $containerName = "blockblobs".generateRandomString();

            
            try {
                
                // Create container.
                $blobClient->createContainer($containerName, $createContainerOptions);

                $myfile = fopen($_FILES["fileToUpload"]["tmp_name"], "r") or die("Unable to open file!");
                fclose($myfile);

                $fileToUpload = strtolower($_FILES["fileToUpload"]["name"]);
                
                // $content = fopen($_FILES["fileToUpload"]["name"], "r");

                $check = fopen($_FILES["fileToUpload"]["tmp_name"], "r");
                

                //Upload blob
                $blobClient->createBlockBlob($containerName, $fileToUpload, $check);

                // List blobs.
                $listBlobsOptions = new ListBlobsOptions();
                $listBlobsOptions->setPrefix($fileToUpload);

                // echo "These are the blobs present in the container: ";

                do{
                    $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                    foreach ($result->getBlobs() as $blob)
                    {
                        // echo $blob->getName().": ".$blob->getUrl()."<br />";
                        $url = $blob->getUrl();
                    }
                
                    $listBlobsOptions->setContinuationToken($result->getContinuationToken());
                } while($result->getContinuationToken());
            }
            catch(ServiceException $e){
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
            catch(InvalidArgumentTypeException $e){
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
                $code = $e->getCode();
                $error_message = $e->getMessage();
                echo $code.": ".$error_message."<br />";
            }
        }
        return $url;
        
    }

    if(isset($_POST['submit'])){
        $result = getData();
        echo '<script type="text/javascript"> processImage("'.$result.'"); </script>';
        
    }
    ?>
   
