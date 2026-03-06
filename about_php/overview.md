Php is an interpreted server side language. Its written in C, the runtime is called Zend engine. Zend engine parses, compiles, and executes the php scripts. Zend also has a set of extensions ex. PDO (db stuff), mysqli, and curl.

# Zend

Zend Engine
   |
   +-- PHP Core
   |
   +-- Extensions (PDO, mysqli, curl, gd, etc)


PHP source code
      |
      v
Lexer (tokenizer)
      |
      v
Parser -> AST
      |
      v
Opcode compilation
      |
      v
Zend VM executes opcodes


# OPcache
without OPcache:
request
  -> parse
  -> compile
  -> execute

with OPcache:
request
  -> load cached opcode
  -> execute


# PHP-FPM
Keeps the worker process alive so we dont have to spawn a new interpreter each time.


                 +----------------------+
                 |   php-fpm master     |
                 | reads config         |
                 | owns pools           |
                 +----------+-----------+
                            |
          --------------------------------------------
          |                                          |
          v                                          v
+-------------------------+              +-------------------------+
| Pool: www               |              | Pool: admin             |
| listen = /run/php.sock  |              | listen = 127.0.0.1:9001 |
| user = www-data         |              | user = admin-user       |
| pm = dynamic            |              | pm = ondemand           |
+-----------+-------------+              +-----------+-------------+
            |                                            |
     -------------------                           ----------------
     |        |       |                           |              |
     v        v       v                           v              v
 worker1  worker2  worker3                    worker1        worker2

Important point: one PHP-FPM worker can only actively execute one request at a time.
So concurrency comes from multiple child processes, not from one worker multitasking. That is why pm.max_children matters so much. The PHP docs define pm.max_children as the limit for simultaneously alive child processes in a pool


# Intepreted language
Incorrect shorthand used for the thing that makes PHP to opcodes run by Zend VM
When people say “PHP is interpreted”, they usually mean:
- you write PHP source
- PHP runtime reads it on request
- Zend Engine compiles it into opcodes
- Zend VM executes those opcodes


PHP source to opscodes
-> lexer (break up into parts)
-> parser (checks validity and build AST)
    -> AST (built by partser, explains what the code means)
-> opcodes (code ZendVM understands)
-> Zend VM executes opcodes (runs opcodes)
