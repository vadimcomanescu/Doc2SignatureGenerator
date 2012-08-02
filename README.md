Doc2SignatureGenerator
======================

This class can be used to generate function and class signatures for a given php extension
in accordance to the php documentation.

Basicaly you just provide the extension name, the directory where the php documentation is
downloaded and unarchived and the file where you want your output to be written.

For example: Doc2SignatureGenerator::generate('ssh2', '/tmp/output', 'myhome/php_doc_dir') will generate 
all the method signatures for the ssh2 extension and will put the results in /tmp/output

The output will be written as php code. 
This is very useful when you want to generate files with extension function and class 
signatures so they can be added to a php project include path when working with a PHP IDE like Zend or Netbeans.

