dbMap_PHP
=========

Once we finished our database (MySQL) we need to create HTML objects to populate our database. This php class was developed to mapping 
a database then it creates classes (as many as your database tables). 
Inside of each of those previus classes, the code creates methods to gererate some HTML/HTML5 objects that will be in hand and will 
facilitate the code, when we are coding for a large project.The aim of this project is to make a kind of shortcut to prevent the boring code task.
Inside of each class, we can find general insert, delet and update methods that acts directlly over his tabes. 

Portuguese:
Depois de criada a nossa base de dados (MySQL), precisamos criar objetos HTML para entrar os dados na nossa base de dados. Esta classe foi desenvolvida
para mapear a base de dados que será enviada como parâmetro do método <em>myDbMap::Fn_dbMap($savePath, $dsn, $username, $password, $db);</em> e em seguida,
dinamicamente criar classes e métodos que perimitirão a criação de objetos HTML ou HTML5 já preparados para BOOTSTRAP 3, jQuery com AJAX.
