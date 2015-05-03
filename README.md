<h1>REST API</h1>
<p>
	Шаблон строки запроса к API: <code>/api/{collection_name}/[{item_id|collection_method}/[{item_method|model_method|method_parametr}/][{method_parametr}/]]</code>.<br>
	<br><br>
	<h5>Общие GET-параметры запросов к API:</h5>
	<dl>
		<dt class="request__param_name"><q>debug</q></dt>
			<dd class="request__param_description">При указании данного параметра, в вывод результатов запросов будут добавлены тестовые данные фреймворка.</dd>
		<dt class="request__param_name"><q>bulk<.q></dt>
			<dd class="request__param_description">
				Параметр указывает API в каком объеме должны быть возвращены данные результата запроса.
				<br><br>
				Возможные значения:<br>
				<q>ids</q> - результатом запроса является массив идентификаторов;<br>
				<q>compact</q> - выполняется выборка данных только из источника коллекции сущностей, <strong>значение по умолчанию для коллекций</strong>;<br>
                <q>full</q> - данные модели дополняются данными связанных сущностей.<br>
			</dd>
		<dt><q>fields</q></dt>
			<dd>
            	Перечень запрашиваемых атрибутов, переданных через запятую. Перестает влиять на результат запроса при указании параметра <q>bulk</q>.<br>
                По умолчанию результат запроса содержит все поля источника данных, которые не упоминаются в <q>excluded_fields</q>.
            </dd>
		<dt><q>excluded_fields</q></dt>
			<dd>
            	Перечень атрибутов модели, которые не должны присутствовать в результате запроса.<br>
                Фреймворк заполняет параметр значением по умолчанию, при переопределении вы увидите неожиданные поля. В общем случае исключаемыми из результатов выборки атрибутами, являются: `id`, `is_del`.
            </dd>
		<dt><q>where</q></dt>
			<dd>
				<p>
					Условие выборки из источника данных. Логические выражения записываются через символ ';', который заменяется фреймворком на логический оператор ' AND '.<br>
					В качестве первого аргумента логического выражения указывается название свойтсва запрашиваемой сущности.<br>
					Второй аргумент является значением поиска.<br>
					Аргументы логического выражения разделены набором символов, подчиняющихся правилу <q>/:[<|>]{0,1}[=]{0,2}:/</q>.
				<p>
				<blockquote>
					Фреймворк проверяет наличие полей в источнике данных сущности, которые были переданы в выражениях параметра.<br>
					Использование знака равенства, для строкового поля источника данных, подразумевает его замену на выражение ' LIKE ' в запросе выборки, выполняется поиск по совпадению в поле источника данных.<br>
					Для поиска точного совпадения необходимо использовать знак '=='.
				</blockquote>
			</dd>
		<dt class="request__param_name"><q>count</q></dt>
			<dd class="request__param_description">Количество элементов в ответе.</dd>
		<dt class="request__param_name"><q>offset</q></dt>
			<dd class="request__param_description">Смещение результатов запроса.</dd>
		<dt class="request__param_name"><q>order</q></dt>
			<dd class="request__param_description">Атрибут, по которому выполняется сортировка результатов запроса. Для сортировки по убыванию(DESC) перед названием атрибута добавляется знак <q>'-'</q>.</dd>
	</dl>
	<h3>Примеры целевых запросов(конечных точек) API</h3>
	<p>
		В качестве примера рассматривается API блога.
	</p>
	<p>
		<h4><code>GET: /list/</code></h4>
		<p>Массив статей.</p>
		<h4><code>POST: /list/</code></h4>
		<p>Создание нового элемента коллекции. Возвращает созданную модель.</p>
		<h4><code>GET: /list/{item_id}</code></h4>
		<p>Подробные данные.</p>
		<h4><code>GET: /list/{item_id}/lastmodify</code></h4>
		<p>Дата последнего обновления модели.</p>
		<h4><code>PUT: /list/{item_id}</code></h4>
		<p>
			Редактирование.<br>
			Данные на сохранение передаются в теле запроса.<br>
			Возвращает обработанную модель.
		</p>
		<h4><code>DELETE: /list/{item_id}</code></h4>
		<p>
			Удаление модели. Подразумевает определение ячейки <code>`tb_list`.`is_del`</code> равным <q>1</q>.<br>
			Рекомендуется переопределять этот метод в модели для оптимизации источника данных.
			Возвращает HTTP-статус <q>204 No Content</q>. 
		</p>
	</p>
	<h3>Ветка доступа к данным пользователей</h3>
	<p>
		<h4><code>GET: /users/</code></h4>
		<p>Перечень всех зарегистрированных пользователей сайта. Запрос окончится неудачей, если в параметрах не был передан ключ доступа авторизованного пользователя. В зависимости от переданного ключа доступа возможны два варианта результата запроса: 
			<ul>
				<li>если <q>access_token</q> соответствует простому пользователю сайта, то запрос вернет лишь основные данные пользователей (user_id, userpic, firstname и lastname);</li>
				<li>если <q>access_token</q> идентифицирует администратора сайта, то возвращается полный набор данных о пользователях.</li>
			</ul>
		</p>
		<h4><code>GET: /users/{user_id|self}/</code></h4>
		<p>
			Получение данных о пользователе, идентифициремого по идентификатору, переданному в части запроса <q>{user_id}</q>. В случае отсутствия ключа доступа (<q>access_token</q>) в GET-параметрах запроса возвращаются лишь основные данные: user_id, userpic, firstname и lastname.<br>
			Полные данные о пользователе, например <q>email</q>, запрос вернет только в том случае, если в GET-параметрах запроса был передан ключ доступа администратора сайта.<br>
			Ключевое слово <q>{self}</q>, указанное в запросе вместо <q>{user_id}</q>, используется для получения полных данных пользователя, идентифицируемого по ключу доступа из GET-параметров запроса.
		</p>
		<h4><code>PUT: /users/{user_id|self}/</code></h4>
		<p>Изменение данных пользователя. Результат запроса будет успешным только лишь в том случае, если переданный <q>access_token</q> принадлежит пользователю с идентификатором <q>{user_id}</q>. Если в запросе будет использованно ключевое слово <q>self</q>, то будут отредактированны данные текущего пользователя.</p>
		<h4><code>POST: /users/auth/</code></h4>
		<p>Авторизация пользователя.</p>
	</p>
	<h3>Служебная ветка запросов.*</h3>
	<p>
		<h4><code>GET: /connector/fb.php</code></h4>
		<p>Авторизация пользователя с помощью социальной сети Facebook.com.</p>
		<h4><code>GET: /connector/tw.php</code></h4>
		<p>Авторизация пользователя с помощью социальной сети Twitter.com.</p>
		<h4><code>GET: /connector/vk.php</code></h4>
		<p>Авторизация пользователя с помощью социальной сети VK.com.</p>
		<h4><code>POST: /upload/</code></h4>
		<p>Загрузка файлов на сервер.</p>
	</p>
	<br><br>
	<h2>Backbone.php</h2>
	<p>
		Программная среда API реализована по образу и подобию <a href="" target="_blank">Backbone.js</a>.
		Модели и Коллекции являются компонентами фреймворка, наследниками от абстрактного класса <q>Component</q>.
	</p>
	<br><br>
	
	<h2>Конечные точки API как публичные методы объектов.</h2>
	<p>
		...
	</p>
	<pre>
	class Items extends Collection {
		public $ModelClass = Item;
		
		/** Gun API Methods
		 * param $options - хеш параметров из программной среды,
		 *                  обычно это массив $_GET, $_POST или $_PUT
		 *  _______________   __________________
		 *  request_method | | collection_method
		public function  get_method($method_parametr, $options=array()) {
			...
			return $result;
		}
		public function post_method($method_parametr, $options=array()) {
			...
			return $result;
		}
		public function put_method($method_parametr, $options=array()) {
			...
			return $result;
		}
		public function delete_method($method_parametr, $options=array()) {
			...
			return $result;
		}
	}
	</pre>
	
	<h3>Пользовательские методы HTTP-запросов.</h3>
	<p>
		Объявляются в классе, в зависимости от метода запроса, с приставкой: 'get_', 'post_', 'put_' или 'delete_'.
	</p>
	
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
		public function get_lastmodify() {
			$_ts = $this->_table->select('updated')
						->order('`updated` desc')
						->limit(1)
						->fetchColumn();
			return date("c", $_ts);
		}
		
		public function put_method(array $uri_parametrs) {
			...
			return $result;
		}
		public function delete_method(array $uri_parametrs) {
			...
			return $result;
		}
	}
	</pre>
	<hr>
	<hr>
	<p>
		Спасибо <a href="http://backbonejs.org/" target="_blank">Backbone.js</a>, <a href="https://github.com/interagent/http-api-design/blob/master/README.md">HTTP API Design Guide</a> и заочно <a href="https://ru.wikipedia.org/wiki/WebDAV">WebDAV</a>!
	</p>
</p>