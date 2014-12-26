<?php

/**
 * Description of dbMap_PHP
 *Esta classe irá criar uma outra classe que será o mapa da base de dados selecionada pelo usuário.
 *A classe criada será de natureza estática, o que permite acesso aos métodos sem a necessidade de se criar objetos.
 * 
 * NOTA:
 * Esta é a primeira versão do projeto, versão teste. 
 * Logo, ela não será preparada para gerar conexões PDO_MySQL  ou MySQLi.
 *  
 * 
 * Para inicializar a classe:
 * 1-Criar o seu banco de dados.
 * 2-Recuperar os parâmetros de acesso ao seu banco de dados ($dsn,$username,$password,$db).
 * 3-Na página index, 
 * 3.1 - Carregar esta classe myDbMap include_once '../myPath/dbMap_PHP.php'; .
 * 3.2 - Usando os parâmetros corretos, chamar o método que vai mapear o banco de dados.
 *       - Exemplo ===> echo myDbMap::Fn_dbMap($savePath,$dsn,$username,$password,$db);
 * 4 - Ao fim da criação da classe será exibido no browser endereço completo onde a classe foi criada.
 * 5 - Se desejar poderá deletar este arquivo dbMap_PHP.php, caso modificações na base de dados não estejam previstas.
 *       Não delete o arquivo se modificações estão previstas.
 * 6 - Irá restar somente a classe recém criada yourDBnameMap.php.
 * 7 - Repetir o passo 3 usando o caminho para a classe recém criada, em todas as páginas onde se deseja criar os objetos HTML, bootstrap ready! 
 * 8 - 
 * @author Igor
 */
class myDbMap {
 
    private static $dsn = "localhost";
    private static $username = "root";
    private static $password = "";
    private static $db = "dbcasstest";
    private static $dbEstrut = "";
 
    //Conecta com a base de dados e retorna o link de conexão em caso de sucesso.
    static function dbConnect($db=NULL) {
            $link = mysql_connect(self::$dsn, self::$username, self::$password);
            if (!$link) {
                    echo "No SERVER connection ==> " . mysql_error();
            } 
            else {
                    if(is_null($db)){
                     $db=self::$db;
                    }
                    if (!mysql_select_db($db)) {
                            echo "No DB connetion ==> " . mysql_error();
                    } 
                    else {
                            return $link;
                    }
            }
    }
    //Encerra a conexão previamente estabelecida.
    static function dbClose($link) {
        mysql_close($link);
    } 
    //Recuperação da estrutura da base de dados que será mapeada. 
    static function dbEstrutura($db) {
        $link = self::dbConnect();
        $sql = "SHOW TABLES FROM ".$db;
        $result = mysql_query($sql);
        if (!$result) {
            $resultadoErro = mysql_error();
            self::dbClose($link);
            return $resultadoErro;
        } 
        else {
                  xdebug_break();
                 
            while ($row = mysql_fetch_row($result)) {
                $sqlField = "SHOW COLUMNS FROM $row[0]";
                $resultField = mysql_query($sqlField);
                if (!$resultField) {
                    $resultadoErro = mysql_error();
                    self::dbClose($link);
                    return $resultadoErro;
                } 
                
                while ($field = mysql_fetch_row($resultField)) {
                    $arrFields[]=$field[0];
                }
                $arrayEstrutura[strtoupper($row[0])] = $arrFields;
                $arrFields=array();
            }
            xdebug_break();
            self::dbClose($link);
            self::$dbEstrut =  json_encode($arrayEstrutura);//Grava o mapa da base de dados na variável privada $dbEstrut da classe no formato JSON
            return $arrayEstrutura; //Retorna como array
        }        
    }
    
    static function createSaveFileDir($path,$filemane,$data,$fileExtent) {
        if(!is_dir($path)){
            mkdir($path);
         }//Se $dir não é um diretório (não existe). Então cria.
         $h=  opendir($path);//Se $dir é um diretório e já existe, então abre.
          while($file = readdir($h)){
               if($file !="." && $file != ".."){
                    if($file===$filemane){
                      unlink("$path/".$file);
                     }//Deleta todos arquivos no diretório $dir, se eles já existem com o nome especificado em $filemane.$fileExtent
               }
           }
          closedir($h);  
           $name = $path."/".$filemane.".$fileExtent";
           if (!($handle2 = fopen($name, "w"))) {
               die("Cannot open the file");
           }
           else{
            fwrite($handle2, $data);
            fclose($handle2);        
           return  $name;
           }
    }
    
    
    //Função de mapeamento da base de dados e criação da classe
    static function Fn_dbMap($savePath,$dsn,$username,$password,$db) {
     $str="<?php \n";
     $str.="class dbBase {\n\n\n";
     
     $dbEst = self::dbEstrutura($db);
     $jsonDbEst = json_encode($dbEst);
     
     $str.="static \$dsn =\"$dsn\";\n" ;
     $str.="private static \$username =\"$username\";\n"    ;
     $str.="private static \$password =\"$password\";\n" ;
     $str.="private static \$db =\"$db\";\n" ;
     $str.="private static \$dbEstrut ='".$jsonDbEst."';\n\n\n\n";
     
     
$str.=<<<EOF
//Conecta com a base de dados e retorna o link de conexão em caso de sucesso.
static function dbConnect() {
            \$link = mysql_connect(self::\$dsn, self::\$username, self::\$password);
            if (!\$link) {
                    echo "No SERVER connection ==> " . mysql_error();
            } 
            else {
                    if (!mysql_select_db(self::\$db)) {
                            echo "No DB connetion ==> " . mysql_error();
                    } 
                    else {
                            return \$link;
                    }
            }
    }\n
EOF;
     
     
$str.=<<<EOF
static function dbClose(\$link) {
        mysql_close(\$link);
}\n
EOF;

$str.=<<<EOF
/**
Embora a variável private static \$dbEstrut, seja inicializada com um objeto JSON do mapa da base de dados,
Essa função é imoprtante para garantir a atualização do mapeamento mesmo depois que a classe foi criada e a 
 base de dados modificada.
*/
static function bdEstrutura() {
    return json_decode(self::\$dbEstrut,TRUE);
}\n
EOF;

$str.=<<<EOF
/**
* 
* @param type \$tabName
* @param type \$selectFields
* @param type \$complemento
* @param type \$arrUnidimensional
* @param type \$asIndex 
* @return type
* 
* \$tabName -> Pode ser uma STRING, um único nome de tabela, com ou sem aspas ou single quote, por exemplo \$tabName="tab_name", \$tabName=tab_name, \$tabName='tab_name'.
* Pode ser também uma array de nome de tabelas envolvidas na query, por exemplo \$tabName=array("tab1_name","tab2_name","tab3_name"), tambem com ou sem aspas ou single quote. Pode ser também uma array de statements (frases) pertinentes a clausula SELECT, por exelpo \$tabName=array("tab1_name AS tb1","tab2_name AS tb2","tab3_name AS tb3").
* 
* \$selectFields -> Pode ser uma STRING, um único nome de campos, com ou sem aspas ou single quote, por exemplo \$selectFields="field1" ou \$selectFields="field1,field2". Aspas ou sigle quote obrigatório, se o número de campos for maior do que 1.  
* 
* \$complemento -> Uma STRING represnetando o complemento da frase select, exemplo \$complemento="WHERE field1=field3 ORDER BY field1"
* 
* \$arrUnidimensional - > A função sempre retornará uma array multidimensional se houver mais do que um registro com reposta da query. Porém se a resposta for somente um registro, haverá a opção de retornar uma array MULTIDIMENSIONAL OU UNIDIMENSIONAL. Será unidemensional par default. Se for desejada uma array nidimensional, o valor  de \$arrUnidimensional deve ser FALSE. Exemplo \$arrUnidimensional=FALSE
* 
* \$asIndex -> Seleciona qual o nome do campo que vai servir como index da array, baseado nos campos da tabela da consulta sql, onde o primeiro campo tem index ZERO.. Nota: se for esperado mais de uma linha no resultado, o valor escolhico como index não pode se repetir. Se for escolher um valor que se repete, terá que alterar o código, tirando os comentários. 
* 
*/
static function MySql_Select(\$tabName, \$selectFields=NULL,\$complemento=NULL,\$arrMultidimensional=TRUE,\$asIndex=NULL) {
        \$link = self::dbConnect();
        
        if(gettype(\$tabName)==="array"){
            //Cria a frase das tabelas que serão utilizadas no select statement
            foreach (\$tabName as \$value) {
                \$tabs .= " " . \$value . ",";
            }
            //Retira a ultima virgula
            \$tabs = trim(\$tabs,",");
            \$tabStatement = \$tabs;
            #\$sql = "SELECT * FROM \$tabs \$complemento";
            
        }
        else{
            \$tabStatement = \$tabName;
        }
        
                
        if(gettype(\$selectFields)==="array"){
            if(count(\$selectFields)>0){
                //Cria a frase dos campos que serão selecionados no select
                foreach (\$selectFields as \$value) {
                    \$fields .= " " . \$value . ",";
                }
                //Retira a ultima virgula
                \$fields = trim(\$fields,",");  
                \$fieldStatement=\$fields;
            }
        }
        else{
            if(\$selectFields===NULL || \$selectFields===""){
                \$fieldStatement="*";
            }
            else{
                \$fieldStatement=\$selectFields;
            }
        }
        
        \$sql = "SELECT \$fieldStatement FROM \$tabStatement \$complemento";   
        \$result = mysql_query(\$sql);
        //\$resourceType = get_resource_type(\$result);
        
        if (!\$result) {//Retorna o typo de erro ocorrido
            \$resultadoErro = mysql_error();
            
            self::dbClose(\$link);
            return \$resultadoErro;
        } 
        else {
                \$nbRow = mysql_num_rows(\$result);
                if (\$nbRow === 0) {
                    \$arrayTot=array();
                } 
                elseif (\$nbRow === 1) {//Se o resultado for 1 linha, existe a opção de reornar uma array multidimensional ou uma array UNIdimensional
                        if(\$arrMultidimensional){       
                                while (\$row = mysql_fetch_assoc(\$result)) {     
                                    //\$arrayTot[] = \$row;
                                    if(!is_null(\$asIndex)){
                                     \$arrayTot[\$row[\$asIndex]] = \$row;
                                    }
                                    else{
                                        \$arrayTot[] = \$row;
                                    }
                                }
                        }
                        else{
                                while (\$row = mysql_fetch_assoc(\$result,MYSQL_ASSOC)) {
                                    \$arrayTot = \$row;
                                }
                        }
                } 
                elseif (\$nbRow > 1) {//Se o resultado for maior do que 1 elemnto a array só poderá ser MULTIdimensional
                    \$arrI=NULL;
                   while (\$row = mysql_fetch_assoc(\$result)) {
                         //\$arrayTot[] = \$row;
                         if(!is_null(\$asIndex)){
                          \$arrayTot[\$row[\$asIndex]] = \$row;
                          //\$arrId=\$row[\$asIndex];
                         }
                         else{
                             \$arrayTot[] = \$row;
                         }
                    }
//                    if(!is_null(\$arrId)){
//                     \$arrIntern[\$arrId]=\$arrayTot;
//                     \$arrayTot=array();
//                     \$arrayTot=\$arrIntern;
//                    }
                }//Fim do elseif

                self::dbClose(\$link);
                return \$arrayTot;
        }
    }\n\n
EOF;

$str.=<<<EOF
/**
* 
* @param string \$tabName
* @param array \$valores
* @param array \$dbEstrutura 
* @return type
* 
* Se a array da \$dbEstrutura não for fornecida então a função irá fazer uma consulta na base dados para procurar sua estrutura.
* Isso permite economizar tempo, pois se já possuimos a array estrura para uma sequencia de inserts nao será feita 
* uma consulta na base de dados a cada insert 
*/
static function Insert_Geral(\$tabName, array \$valores, \$dbEstrutura = NULL,\$lastId=TRUE,\$strErro=TRUE) {
        //Estrutura do Bd em forma de array        
        if (\$dbEstrutura === NULL) {
         \$dbEst = self::bdEstrutura();
        } else {
         \$dbEst = \$dbEstrutura;
        }

        //Forma a Estring dos campos a serem inseridos valores
        foreach (\$dbEst[\$tabName] as \$value) {
         \$fields .= \$value . ",";
        }
        //Retira a ultima virgula
        \$fields = trim(\$fields, ",");
        //Forma a string dos valores a serem inseridos
        foreach (\$valores as \$value) {
             if (\$value == NULL) {
                 \$value = "NULL";
             } 
             elseif (is_string(\$value)) {
                 \$value = "\"\$value\"";
             } 
             else {
                 \$value = \$value;
             }
             \$strVal.=\$value . ",";
        }
        //Retira a ultima virgula
        \$strVal = trim(\$strVal, ",");
        \$link = self::dbConnect();
        \$sql = "INSERT INTO  \$tabName ( \$fields ) VALUES ( \$strVal )";
        mysql_query(\$sql);
        
        //Returns the number of affected rows on success, and -1 if the last query failed.
        \$resultado = mysql_affected_rows();
        
        if (\$resultado > 0) {
               \$LId = mysql_insert_id();
               self::dbClose(\$link);
               if(\$lastId){//Se foi requisitado 
                   return \$LId;
               }
               else{
                   return \$resultado;
               }
        } 
        else {
                if(\$strErro){
                     \$erro = mysql_error();
                     self::dbClose(\$link);
                     return \$erro;
                }
                else{
                      self::dbClose(\$link);
                      return \$resultado;
                }
        }
 }\n
EOF;

$str.=<<<EOF
static function UpdateGeral(\$tabName,array \$fieldVal, \$complemento=NULL) {
       

     //If you omit the WHERE clause, all records will be updated!
     
     foreach (\$fieldVal as \$key => \$value) {      
          if(is_string(\$value)){
           \$fiedVal.="\$key=\"\$value\",";
          }
          else{
           \$fiedVal.="\$key=\$value,";
          }
     }        
     \$strVal = trim(\$fiedVal,",");
        \$link = self::dbConnect();
        \$sql = "UPDATE  \$tabName SET  \$strVal  \$complemento ";   
        mysql_query(\$sql);
        //Returns the number of affected rows on success, and -1 if the last query failed.
        \$resultado = mysql_affected_rows();
        if(\$resultado>0){            
            self::dbClose(\$link);
            return \$resultado;
        }
        else{
            \$erro=mysql_error();
            self::dbClose(\$link);
            return \$erro;
        }
    }\n
EOF;

$str.=<<<EOF
static function DeleteGeral(\$tabName, \$complemento=NULL) {
                
        \$link = self::dbConnect();
        \$sql = "DELETE FROM  \$tabName \$complemento";   
        mysql_query(\$sql);
        
        ///* this should return the correct numbers of deleted records */
        \$resultado = mysql_affected_rows();
        if(\$resultado>0){            
            self::dbClose(\$link);
            return \$resultado;
        }
        else{
            \$erro=mysql_error();
            self::dbClose(\$link);
            return \$erro;
        }
    }\n
EOF;

$str.=<<<EOF
static function HTML_Input(array \$input_Attr) {
        \$attrInput = null;
        foreach (\$input_Attr as \$key => \$value) {
            if (is_string(\$key)) {
                \$attrInput .= " " . \$key . "=\"" . \$value . "\"";
            } else {
                \$Label = \$value;
            }
        }
        //Geraçao do objeto <input> personalizado.
        return "<input " . \$attrInput . "/>" . \$Label;
    }\n
EOF;

$str.=<<<EOF
static function HTML_Select(array \$conteudoA, \$selected = NULL,\$disabled=NULL, \$attrSelectA = NULL, \$multiple=NULL,\$classMarkerCSS=NULL) {
        //Só cria o cboBox se na array \$conteudoA houver ao menos 1 elemento
        if (count(\$conteudoA) > 0) {
            \$disableControl=1;
            //Cria a frase de atributuos de select
            \$attrSelect = null;
            
            //Geracao dos atributos da tag <select>
            if(gettype(\$attrSelectA)==="array" && count(\$attrSelectA)){
                foreach (\$attrSelectA as \$key => \$value) {
                    \$attrSelect .= " " . \$key . "=\"" . \$value . "\"";
                }
            }
            
            //echo HTMLCLASS::HTML_Select3(\$arr)."<br>";//Somente uma array
            if(\$selected===NULL && \$disabled===NULL){
                foreach (\$conteudoA as \$key =>\$value) { 
                    //---------------------------------------------------------------------------------------------------------------------
                    \$markCSS="";
                    //Recupera o primeiro caractere
                    \$firstChar = substr(\$key,0, 1);
                    //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                    if(ord(\$firstChar)===42){
                        \$key= substr(\$key, 1);
                        \$markCSS = "class=\"\$classMarkerCSS\"";
                    }
                    //---------------------------------------------------------------------------------------------------------------------
                    \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                }
            }  
            //echo HTMLCLASS::HTML_Select3(\$arr,NULL,1)."<br>";//Selected NULL e disable INTEGER 
            elseif (\$selected===NULL &&(gettype(\$disabled))==="integer") {
                foreach (\$conteudoA as \$key =>\$value) { 
                    //---------------------------------------------------------------------------------------------------------------------
                    \$markCSS="";
                    //Recupera o primeiro caractere
                    \$firstChar = substr(\$key,0, 1);
                    //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                    if(ord(\$firstChar)===42){
                        \$key= substr(\$key, 1);
                        \$markCSS = "class=\"\$classMarkerCSS\"";
                    }
                    //---------------------------------------------------------------------------------------------------------------------
                    if(\$key===\$disabled){   
                        \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";
                    }
                    else{
                       \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>"; 
                    }
                    
                }
            }
            //echo HTMLCLASS::HTML_Select3(\$arr,NULL,"e")."<br>";//Selected NULL e disable string QUE EXISTE
            //echo HTMLCLASS::HTML_Select3(\$arr,NULL,"b")."<br>";//Selected NULL e disable string QUE NÃO EXISTE
            elseif (\$selected===NULL && \$disabled!==NULL&&(gettype(\$disabled))==="string") {                
                //Se string  \$disabled existe na lista   
                if(in_array("\$disabled", \$conteudoA)){//Se a label para ser desabilitada existe
                        foreach (\$conteudoA as \$key =>\$value) { 
                            //---------------------------------------------------------------------------------------------------------------------
                            \$markCSS="";
                            //Recupera o primeiro caractere
                            \$firstChar = substr(\$key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                            if(ord(\$firstChar)===42){
                                \$key= substr(\$key, 1);
                                \$markCSS = "class=\"\$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                            if(\$value===\$disabled){
                                \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";
                            }
                            else{
                                \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>"; 
                            }
                            
                        } //Fim de foreach
                    }//Fim de if(\$disabledExist)                    
                //Se string  \$disabled não existe na lista ==> Vai criar no primeiro lugar da lista e desabilitar
                else{ 
                        //--------------------------------------------------------------------------------------------------------------------- 
                        \$markCSS=NULL;
                        if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        \$allValues .= "<option \$markCSS disabled value=\"0\">" . \$disabled . "</option>";
                        
                        foreach (\$conteudoA as \$key =>\$value) {  
                            //---------------------------------------------------------------------------------------------------------------------
                            \$markCSS="";
                            //Recupera o primeiro caractere
                            \$firstChar = substr(\$key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                            if(ord(\$firstChar)===42){
                                \$key= substr(\$key, 1);
                                \$markCSS = "class=\"\$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                           \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                        }
                    }
            }
            //echo HTMLCLASS::HTML_Select3(\$arr,1,NULL)."<br>";//Selected integer e disable NULL
            elseif (gettype(\$selected)==="integer"&&\$disabled===NULL) {                
                    foreach (\$conteudoA as \$key =>\$value) { 
                        
                           //---------------------------------------------------------------------------------------------------------------------
                            \$markCSS="";
                            //Recupera o primeiro caractere
                            \$firstChar = substr(\$key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                            if(ord(\$firstChar)===42){
                                \$key= substr(\$key, 1);
                                \$markCSS = "class=\"\$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------
                        
                            if(\$key===\$selected){
                                \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                            }
                            else{
                               \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                            }
                    }
            }         
            //echo HTMLCLASS::HTML_Select3(\$arr,1,1)."<br>";//Selected integer e disable A MESMA integer
            //echo HTMLCLASS::HTML_Select3(\$arr,1,2)."<br>";//Selected integer e disable OUTRA integer
            elseif (gettype(\$selected)==="integer"&&gettype(\$disabled)==="integer") {                
                    foreach (\$conteudoA as \$key =>\$value) {
                            //---------------------------------------------------------------------------------------------------------------------
                            \$markCSS="";
                            //Recupera o primeiro caractere
                            \$firstChar = substr(\$key,0, 1);
                            //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                            if(ord(\$firstChar)===42){
                                \$key= substr(\$key, 1);
                                \$markCSS = "class=\"\$classMarkerCSS\"";
                            }
                            //---------------------------------------------------------------------------------------------------------------------                   
                        
                        
                            if(\$key===\$selected){
                                if(\$key===\$disabled){//Sera disabled e selected
                                    \$allValues .= "<option \$markCSS selected=\"selected\" disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                }
                                else{//Sera somente  selected
                                    \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                                }
                            }
                            else{
                                if(\$key===\$disabled){//Sera disabled 
                                    \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                }
                                else{//Nem disabled nem selected
                                    \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                }
                            }
                    }
            }
            //echo HTMLCLASS::HTML_Select3(\$arr,2,"i")."<br>";//Selected integer e disable a mesma string escolhida para Selected
            //echo HTMLCLASS::HTML_Select3(\$arr,2,"o")."<br>";//Selected integer e disable string q não é a mesma de selected
            //echo HTMLCLASS::HTML_Select3(\$arr,2,"z")."<br>";//Selected integer e disable string q não existe
            elseif (gettype(\$selected)==="integer"&&gettype(\$disabled)==="string") {  
                 if(in_array("\$disabled", \$conteudoA)){//O disabled esta na array
                     
                            foreach (\$conteudoA as \$key =>\$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                
                                if(\$key===\$selected){//Selecionado
                                    if(\$value===\$disabled){//Sera disabled e selected
                                        \$allValues .= "<option \$markCSS selected=\"selected\" disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                    }
                                    else{//Sera somente  selected
                                        \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                                    }
                                }
                                else{
                                        if(\$value===\$disabled){//Sera disabled 
                                                \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                        }
                                        else{//Nem disabled nem selected
                                                \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                        }
                                }
                            }//Fim do foreach            
                 }
                 else{//O disabled não esta na array
                        //--------------------------------------------------------------------------------------------------------------------- 
                        \$markCSS=NULL;
                        if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        \$allValues .= "<option \$markCSS disabled value=\"0\">" . \$disabled . "</option>";
                        foreach (\$conteudoA as \$key =>\$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------  
                              if(\$key===\$selected){//Selecionado                                  
                                        \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";                                   
                              } 
                              else{
                                  \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                              }
                        }
                 }      
            }
            //echo HTMLCLASS::HTML_Select3(\$arr,"e",NULL)."<br>";//Selected string existe e disable NULL
            //echo HTMLCLASS::HTML_Select3(\$arr,"b",NULL)."<br>";//Selected string não existe e disable NULL
            elseif (gettype(\$selected)==="string"&&\$selected!==NULL&&\$disabled===NULL) {     
                if(in_array("\$selected", \$conteudoA)){
                        foreach (\$conteudoA as \$key =>\$value) { 
                               //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                            
                            if(\$value===\$selected){
                                \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                            }
                            else{
                                \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>"; 
                            }
                        } 
                }
                else{ 
                        //--------------------------------------------------------------------------------------------------------------------- 
                        \$markCSS=NULL;
                        if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                        //---------------------------------------------------------------------------------------------------------------------
                        \$allValues .= "<option selected=\"selected\" value=\"0\">" . \$selected . "</option>";
                        foreach (\$conteudoA as \$key =>\$value) {    
                                //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                           \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                        }
                    }
            } 
            //echo HTMLCLASS::HTML_Select3(\$arr,"e",2)."<br>";//Selected string existe e disable o mesmo elemento
            //echo HTMLCLASS::HTML_Select3(\$arr,"b",2)."<br>";//Selected string não existe e disable outro elemento
            elseif (gettype(\$selected)==="string"&&gettype(\$disabled)==="integer") {
                    if(in_array("\$selected", \$conteudoA)){//O \$selected esta na array
                               foreach (\$conteudoA as \$key =>\$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                   if(\$value===\$selected){//Selecionado
                                       if(\$key===\$disabled){//Sera disabled e selected
                                           \$allValues .= "<option \$markCSS selected=\"selected\" disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                       }
                                       else{//Sera somente  selected
                                           \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                                       }
                                   }
                                   else{
                                           if(\$key===\$disabled){//Sera disabled 
                                                   \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";
                                           }
                                           else{//Nem disabled nem selected
                                                   \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                           }
                                   }
                               }//Fim do foreach            
                    }
                    else{//O \$selected não esta na array
                            //--------------------------------------------------------------------------------------------------------------------- 
                            \$markCSS=NULL;
                            if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                            //---------------------------------------------------------------------------------------------------------------------
                           \$allValues .= "<option \$markCSS selected=\"selected\" value=\"0\">" . \$selected . "</option>";
                           foreach (\$conteudoA as \$key =>\$value) { 
                                //---------------------------------------------------------------------------------------------------------------------
                                \$markCSS="";
                                //Recupera o primeiro caractere
                                \$firstChar = substr(\$key,0, 1);
                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                if(ord(\$firstChar)===42){
                                    \$key= substr(\$key, 1);
                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                }
                                //---------------------------------------------------------------------------------------------------------------------
                                 if(\$key===\$disabled){//Selecionado                                  
                                           \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";                                   
                                 } 
                                 else{
                                     \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                 }
                           }
                    }      
            }
            //echo HTMLCLASS::HTML_Select3(\$arr,"e","b")."<br>";//Selected string existe e disable um elemento que não existe
            //echo HTMLCLASS::HTML_Select3(\$arr,"b","e")."<br>";//Selected string não existe e disable um elemento que existe
            //echo HTMLCLASS::HTML_Select3(\$arr,"b","b")."<br>";//Selected string não existe e disable o mesmo elemento - que não existe
            //echo HTMLCLASS::HTML_Select3(\$arr,"b","p")."<br>";//Selected string não existe e disable outro elemento que não existe
            elseif (gettype(\$selected)==="string"&&gettype(\$disabled)==="string") {
                    if(in_array("\$selected", \$conteudoA)){//O \$selected esta na array
                                if(in_array("\$disabled", \$conteudoA)){//\$selected e  \$disabled estão na array
                                        foreach (\$conteudoA as \$key =>\$value){
                                                //---------------------------------------------------------------------------------------------------------------------
                                                \$markCSS="";
                                                //Recupera o primeiro caractere
                                                \$firstChar = substr(\$key,0, 1);
                                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                                if(ord(\$firstChar)===42){
                                                    \$key= substr(\$key, 1);
                                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                                }
                                                //---------------------------------------------------------------------------------------------------------------------
                                                if(\$value===\$selected){//\$val =  \$selected       
                                                    if(\$value===\$disabled){//\$val =  \$selected  &&   \$val =  \$disabled                            
                                                            \$allValues .= "<option \$markCSS selected=\"selected\" disabled value=\"" . \$key . "\">" . \$value . "</option>";                                   
                                                    }
                                                    else{//\$val =  \$selected  &&   \$val <>  \$disabled 
                                                            \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                                                    }                                                                                           
                                                } 
                                                else{//\$val <>  \$selected
                                                        if(\$value===\$disabled){//\$val <>  \$selected  &&   \$val =  \$disabled                            
                                                                \$allValues .= "<option  \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";                                   
                                                        }
                                                        else{//\$val <>  \$selected  &&   \$val <>  \$disabled 
                                                                \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                                        }
                                                }
                                        }
                                }
                                 else{//\$selected esta e \$disabled não estão na array
                                     //--------------------------------------------------------------------------------------------------------------------- 
                                    \$markCSS=NULL;
                                    if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                                    //---------------------------------------------------------------------------------------------------------------------
                                        \$allValues .= "<option \$markCSS disabled>" . \$disabled . "</option>";
                                        foreach (\$conteudoA as \$key =>\$value){
                                                //---------------------------------------------------------------------------------------------------------------------
                                                \$markCSS="";
                                                //Recupera o primeiro caractere
                                                \$firstChar = substr(\$key,0, 1);
                                                //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                                if(ord(\$firstChar)===42){
                                                    \$key= substr(\$key, 1);
                                                    \$markCSS = "class=\"\$classMarkerCSS\"";
                                                }
                                                //---------------------------------------------------------------------------------------------------------------------
                                                if(\$value===\$selected){//\$val =  \$selected       
                                                        \$allValues .= "<option \$markCSS selected=\"selected\" value=\"" . \$key . "\">" . \$value . "</option>";
                                                } 
                                                else{//\$val <>  \$selected
                                                        \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                                }
                                        }
                                 }             
                    }
                    else{//O \$selected não está na array
                        
                                if(in_array("\$disabled", \$conteudoA)){//\$selected não está e  \$disabled está na array
                                        //--------------------------------------------------------------------------------------------------------------------- 
                                        \$markCSS=NULL;
                                        if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                                        //---------------------------------------------------------------------------------------------------------------------
                                        \$allValues .= "<option \$markCSS selected=\"selected\" >" . \$selected . "</option>";
                                        foreach (\$conteudoA as \$key =>\$value){
                                                   //---------------------------------------------------------------------------------------------------------------------
                                                    \$markCSS="";
                                                    //Recupera o primeiro caractere
                                                    \$firstChar = substr(\$key,0, 1);
                                                    //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                                    if(ord(\$firstChar)===42){
                                                        \$key= substr(\$key, 1);
                                                        \$markCSS = "class=\"\$classMarkerCSS\"";
                                                    }
                                                    //---------------------------------------------------------------------------------------------------------------------
                                                   if(\$value===\$disabled){//\$val =  \$selected  &&   \$val =  \$disabled                            
                                                            \$allValues .= "<option \$markCSS disabled value=\"" . \$key . "\">" . \$value . "</option>";                                   
                                                    }
                                                    else {
                                                            \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>"; 
                                                    }
                                        }
                                }
                                else{//\$selected e \$disabled não estão na array
                                        if(\$selected===\$disabled){
                                            //--------------------------------------------------------------------------------------------------------------------- 
                                            \$markCSS=NULL;
                                            if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                                            //---------------------------------------------------------------------------------------------------------------------
                                            \$allValues .= "<option \$markCSS value=\"0\" selected=\"selected\" disabled >" . \$selected . "</option>";//Valor = 0 para strings que não existem na lista e estao desabilitadas
                                        }
                                        else{
                                            //--------------------------------------------------------------------------------------------------------------------- 
                                            \$markCSS=NULL;
                                            if(\$classMarkerCSS<>NULL){\$markCSS = "class=\"\$classMarkerCSS\"";}
                                            //---------------------------------------------------------------------------------------------------------------------
                                            \$allValues .= "<option \$markCSS selected=\"selected\" >" . \$selected . "</option>";
                                            \$allValues .= "<option \$markCSS disabled >" . \$disabled . "</option>";
                                        }                             
                                        foreach (\$conteudoA as \$key =>\$value){     
                                            //---------------------------------------------------------------------------------------------------------------------
                                            \$markCSS="";
                                            //Recupera o primeiro caractere
                                            \$firstChar = substr(\$key,0, 1);
                                            //Testa se está marcado com um *, afeta o controlador \$mark para verdadeiro e limpa a string \$key
                                            if(ord(\$firstChar)===42){
                                                \$key= substr(\$key, 1);
                                                \$markCSS = "class=\"\$classMarkerCSS\"";
                                            }
                                            //---------------------------------------------------------------------------------------------------------------------
                                            \$allValues .= "<option \$markCSS value=\"" . \$key . "\">" . \$value . "</option>";
                                        }
                                } 
                    }      
            } 
            
            if(isset(\$multiple)){
                return "<select multiple " . \$attrSelect . ">" . \$allValues . "</select>"; 
            }
            else{
               return "<select " . \$attrSelect . ">" . \$allValues . "</select>"; 
            }
            
        }
    }\n
EOF;

$str.=<<<EOF

EOF;

     
$str.="}\n\n";//Fim da classe dbBase 



 
foreach ($dbEst as $key => $value) {
      
      
      
            
      $str1.="class $key  {\n\n\n";//Cria uma classe com o nome de cada tabela do banco
      
       
          //Chama classe dbBase
          $str1.="static function ".$key."_Select(){\n";
               $str1.="return dbBase::MySql_Select($key);\n";
          $str1.="}\n";//Fim do Select

          
          //Chama classe dbBase
          $str1.="static function ".$key."_Insert(\$values) {\n";
               $str1.="return dbBase::Insert_Geral(\"$key\", \$values, \$est);\n";
          $str1.="}\n";//Fim do Insert
          
          
          
          $str1.="static function ".$key."_Content() {\n";
          $str1.="echo \"<br/>\";\n";
          $str1.="echo \"<pre>\";\n";
          $str1.="print_r(self::$key"."_Select());\n";
          $str1.="echo \"</pre>\";\n";
          $str1.="echo \"<br/>\";\n";               
          $str1.="}\n";//Fim de _Content. Retorna uma array como  conteúdo da tabela
          
          
          foreach ($value as $fieldName) {
               //Na versao completa verificar as 3 primeiros caracteres do field name.
                        //Se for txt cria um input texto
                        //Se for psw cria input password
                        //Se for rad cria input radio
                        //Se for txA textarea  
                        //Se for chk cria input checkbox
                        //Etc
                       

                        
           
                         
                         $str1.="static function Input_txt_$fieldName(array \$attr=NULL){\n";         
                                     $str1.="\$arrAttrInt=array();\n";
                                     $str1.="\$arrAttrInt[\"id\"]=\"txt_$fieldName\";\n";
                                     $str1.="\$arrAttrInt[\"class\"]=\"\";\n";
                                     $str1.="\$arrAttrInt[\"type\"]=\"text\";\n";
                                     $str1.="\$arrAttrInt[\"value\"]=\"\";\n";
                                     
                              $str1.="if(is_null(\$attr)){\n";
                                   $str1.="\$arrAttr=\$arrAttrInt;\n";
                               $str1.="}\n";
                               $str1.="else{\n";
                                    $str1.="\$arrAttr=  array_merge(\$arrAttrInt,\$attr);\n";
                               $str1.="}\n";
                                     
                               $str1.="return dbBase::HTML_Input(\$arrAttr);";
                        $str1.="\n}\n";
           
           
           
           
           
           
           
           

               
          }
          
           

          $str1.="static function Obj_Select_$key(\$attr = NULL, \$selected = NULL, \$disabled = NULL) {\n";
               $str1.="\$arr=  dbBase::MySql_Select($key,$fieldName);\n";       
               $str1.="return dbBase::HTML_Select(\$arr, \$selected,\$disabled,\$attr,\$multiple);\n"; 
          $str1.="}\n"; 
          
          $str1.="\n\n";
         
          
          $str1.="}\n\n";//FIm da classe
         
     }











     ///////////////////////////////////////////////////////////////////////////////////////////////////
     $filemane=$db."_Map";//A nova classe terá o nome da base de dados mapeada
     $fileExtent="php";
     
     return self::createSaveFileDir($savePath, $filemane, $str.$str1."?>", $fileExtent);
    }
}
