<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        //Chama a classe que vai ler a base de dados e vai gerar a outra classe, com o nome da base de dados lida no 
        //endereço especificado pelo usuário.
        //Esta é uma classe estática, portanto não precisa de um objeto para se acessar os métodos da classe.
        #include_once './myCLASS/dbMap_PHP.php';
        //Chama o método que vai ler o banco de dados, passando alguns parâmetros.
        //$savePath => Local onde será registardo a nova classe,
        //$dsn => path do servidor onde está a base de dados (localhost ???),
        //$username => nome do usuário para acessar a base de dados,
        //$password => password de acesso para a abase de dados ,
        //$db => nome do banco de dados a ser mapeado
        #echo myDbMap::Fn_dbMap("./myCLASS","localhost","root","","dbcasstest");
        include_once './myCLASS/dbcasstest_Map.php';
        
        echo USER_T::Input_txt_NAME();
        echo USER_T::Input_txt_NAME(array("class"=>"form1"));
        echo USER_T::Input_txt_NAME(array("id"=>"myId","class"=>"form2 form3 form4"));
        
        echo USER_T::InputB_txt_NAME(array("id"=>"myId","class"=>"form2 form3 form4"));
        
        
        ?>
    </body>
</html>
