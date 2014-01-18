<h1>REST API</h1>
<p>
	Шаблон строки запроса к API: <code>/api/{collection_name}/[{item_id|collection_method}/[item_method|collection_parametr]]</code>.<br>
	<br><br>
	<h5>Общие GET-параметры для всех запросов (необязательные):</h5>
	<dl>
		<dt><q>fields</q></dt>
			<dd>Перечень запрашиваемых атрибутов модели, переданных через запятую. По умолчанию отдается весь набор данных.</dd>
		<dt><q>count</q></dt>
			<dd>количество сущностей в ответе</dd>
		<dt><q>offset</q></dt>
			<dd>смещение результатов выборки</dd>
		<dt><q>sort</q></dt>
			<dd>Сортировка результатов запроса. В параметре передается название столбца, по которому выполняется сортировка, ее порядок определяется знаком '-' в случае сортировки по убыванию (DESC) и отсутствием знака '-' для сортировки по убыванию (ASC).</dd>
	</dl>
	<h2>Целевые запросы(конечные точки) API<h2>
	<p>
		<h4><code>GET: /blog/</code></h4>
		<p>Массив статей.</p>
		<h4><code>POST: /blog/</code></h4>
		<p>Создание новой статьи.</p>
		<h4><code>GET: /blog/{article_id}</code></h4>
		<p>Подробные данные.</p>
		<h4><code>PUT: /blog/{article_id}</code></h4>
		<p>Редактирование.</p>
	</p>
	<h2>Ветка доступа к данным зарегистрированных пользователей сайта<h2>
	<p>
		<h4><code>GET: /users/</code></h4>
		<h4><code>POST: /users/</code></h4>
		<h4><code>GET: /users/{user_id}</code></h4>
		<h4><code>PUT: /users/{user_id}</code></h4>
		<h4><code>POST: /users/auth</code></h4>
		<h4><code>POST: /users/forgot</code></h4>
		<h4><code>POST: /users/registration</code></h4>
	</p>
	<h2>Служебная ветка запросов.<h2>
	<p>
		<h4><code>POST: /upload/</code></h4>
		<p>Загрузка файлов на сервер.</p>
	</p>
	<br><br>
	<h2>Backbone.php</h2>
	Программная среда API реализована по образу и подобию Backbone.js.<br>
	Сущности сайта описываются с помощью пары классов: Model для описание свойств отдельной сущности и Collection для описания набора сущностей.<br>
	<!--blockquote cite="http://backbonejs.ru/">
		Backbone.js придает структуру веб-приложениям с помощью моделей с биндингами по ключу и пользовательскими событиями, коллекций с богатым набором методов с перечислимыми сущностями, представлений с декларативной обработкой событий; и соединяет это все с вашим существующим REST-овым JSON API.
	</blockquote-->
	<p>
		Пример описания классов модели и коллекции для реализации отдельной сущности сайта:<br>
		<pre>
			class Item extends Model {
				protected $_table = "`db_name`.`table_name`";
				
				/** Gun API Methods
				 * param $options - хеш параметров из программной среды, обычно это массив $_GET
				 * _______________   __________________
				 * request_method | | model_method
				public function get_method($options=array()) {
					...
					return $result;
				}
				public function put_method($options=array()) {
					...
					return $result;
				}
			}
			
			class Items extends Collection {
				public $ModelClass = Item;
				
				/** Gun API Methods
				 * param $options - хеш параметров из программной среды
				 *  _______________   __________________
				 *  request_method | | collection_method
				public function  get_method($options=array()) {
					...
					return $result;
				}
				public function post_method($options=array()) {
					...
					return $result;
				}
			}
		</pre>
	</p>
	<h3>Model</h3>
	<p>
		Экземпляр класса <q>Model</q> имеет следующие методы и свойства:
		
	</p>
	<h3>Collection</h3>
	<p>
		Коллекция определяет свойства и методы, описывающие работу с набором моделей.
		
	</p>
	<p></p>
	<p></p>
	<p></p>
	<p></p>
	<p></p>
</p>