# README

## Instructions
 - Depending on which user is running php or where the directory is, you may need to run the command under sudo as no checks are done on folder permissions.
 - Please run the following command php clean.php [inputfile] [outputmethod]. For example 
     'php clean.php data/points.csv save' 
     'php clean.php data/points.csv print'
 - If you want to change the top speed you can do on line 2. I have set it to 50mph as that feels like a speed at which a taxi will not go above in central London. If need's be this could obviously be set as a argument on the CLI.

## Explanation
To determine what method I needed to use (Out of Range Speed or Standard Deviation) to clean the data I first imported the uncleaned csv into Google Maps to better visualise the problem. I went for out of range speed filtering as it was the easiest and would solve the problem with the data provided.
 

## Author
Leonard Austin