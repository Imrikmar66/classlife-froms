# Idem Pop Up
![idem creative arts school|idem creative arts school](http://www.lidem.eu/wp-content/uploads/Logo_Lidem_20172.png)

Idem Pop Up is an editable pop up with a lot of options :

  - Creating Pop Up as post-type
  - Activation and Pop Up selection on pages
  - Manual or auto activation pop up
  - Mark Pop Up as Default
  - Select container templates
  - Works with timed pop up - default is used if  pop up time end
  - Works with WPBakery Visual Composer

## First step
- Install Contact Form 7
- Install Idem Classlife Forms
- Go to Classlife Forms tab (settings icon)
- Configure the APIKEY and the API URL for submissions
- Create and save a new Contact From with title containing "`Classlife`"

## Second step
Create the form with habitual contact form 7 rules, but with theses others rules : 
    - uses `-` for meta fields : 
    ```
        <label> Prénom (required)
    [text* teacher_name] </label>
    ```
    ```
    <label> Autres Prénoms (required)
    [text* meta-nombre2 ] </label>
    ```
    Here `teacher_name` will not changed and `meta-nombre2` will be retranscrite in `meta[nombre2]`.
    
## Last step
Use ``` [hidden perform "buildForm"] 
    [hidden model"teacher"] ```
    - perform will define type of api request (see the api documentation)
    - model will define which type of model will be affected (see the api documentation)
    
Your form should works now !