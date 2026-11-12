
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>
<body>
 
 
   <?php
     
     include('header.php');

   ?>
      
            
    
   <!-- Esta página contiene una imágen de Error 404 (Página No Encontrada) --> 

   
   
  <section>
   
  
  <!-- ERROR 404 Página No Encontrada  -->

  <div class="container"> 
    <div class="card bg-white border-secondary mt-5 mb-5">  
    <div class="jumbotron">
    <div class="row cont_img_accesoerror mt-2 mb-5"> 

      <div class="text-center lead mt-5 mb-2"><h3><strong>Upss HA OCURRIDO UN ERROR!</strong></h3>
      <img src="imagenes/error404.jpg" class="img-fluid" alt="Error 404"></div> 

      <?php
        if (isset($_GET["pag"])){
     
            echo "<div class='text-center lead mb-2'><h3><strong>AÚN NO REALIZASTE TU PÁGINA ".htmlspecialchars($_GET["pag"], ENT_QUOTES, "UTF-8")." !!!</strong></h3>";
         
        }
      ?>
        
   </div> 

   

  </section>


  <?php
    include('footer.php');
  ?>
   
   <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>