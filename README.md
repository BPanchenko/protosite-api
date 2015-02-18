<h1>REST API</h1>
<p>
	Шаблон строки запроса к API: <code>/api/{collection_name}/[{item_id|collection_method}/[{item_method|method_parametr}/][{method_parametr}/]]</code>.<br>
	<br><br>
	<h5>Общие GET-параметры:</h5>
	<dl>
		<dt class="request__param_name"><q>bulk<.q></dt>
			<dd class="request__param_description">
				Параметр указывает API в каком объеме должны быть возвращены данные результата запроса.
				<br><br>
				Возможные значения:<br>
				<q>ids</q> - результатом запроса является массив идентификаторов;<br>
				<q>compact</q> - выполняется выборка данных только из общей таблицы коллекции сущностей, <strong>значение по умолчанию для коллекций</strong>;<br>
                <q>full</q> - к данным полученным из общей таблицы добавляются результаты запросов к связанным таблицам, <strong>значение по умолчанию для моделей</strong>.<br>
			</dd>
		<dt><q>fields</q></dt>
			<dd>Перечень запрашиваемых атрибутов модели, переданных через запятую. Параметр может изменить влияние параметра <q>bulk<.q>.</dd>
		<dt class="request__param_name"><q>page_id</q></dt>
			<dd class="request__param_description">Идентификатор страницы с которой связаны результаты запроса.</dd>
		<dt class="request__param_name"><q>offset</q></dt>
			<dd class="request__param_description">Смещение результатов запроса.</dd>
		<dt class="request__param_name"><q>count</q></dt>
			<dd class="request__param_description">Количество элементов в ответе.</dd>
		<dt class="request__param_name"><q>sort</q></dt>
			<dd class="request__param_description">Атрибут, по которому выполняется сортировка результатов запроса. Для сортировки по убыванию(DESC) перед названием атрибута указывается знак <q>'-'</q>.</dd>
	</dl>
	<h3>Примеры целевых запросов(конечных точек) API</h3>
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
		<p>Удаление сущности.</p>
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
	Программная среда API реализована по образу и подобию Backbone.js.<br>
	Сущности сайта описываются с помощью классов:
	<ul>
		<li><q>Model</q> для описание свойств отдельной сущности;</li>
		<li><q>Collection</q> для описания набора сущностей.</li>
	</ul>
	<p>
		Пример описания классов модели и коллекции для реализации отдельной сущности сайта:<br>
		<pre>
			class Item extends Model {
				protected $_table = "`db_name`.`table_name`";
				
				/** Gun API Methods
				 * param $options - хеш параметров из программной среды, обычно это массив $_GET
				 * _______________   __________________
				 * request_method | | model_method
				public function GET_method($options=array()) {
					...
					return $result;
				}
				public function GET_lastmodify($params_uri, $body_request) {
					$_ts = $this->table->select('updated')
								->order('`updated` desc')
								->fetchColumn();
					return date("c", $_ts);
				}
				
				public function PUT_method($options=array()) {
					...
					return $result;
				}
				public function DELETE_method($method_parametr, $options=array()) {
					...
					return $result;
				}
			}
			
			class Items extends Collection {
				public $ModelClass = Item;
				
				/** Gun API Methods
				 * param $options - хеш параметров из программной среды,
				 *                  обычно это массив $_GET, $_POST или $_PUT
				 *  _______________   __________________
				 *  request_method | | collection_method
				public function  GET_method($method_parametr, $options=array()) {
					...
					return $result;
				}
				public function POST_method($method_parametr, $options=array()) {
					...
					return $result;
				}
				public function PUT_method($method_parametr, $options=array()) {
					...
					return $result;
				}
			}
		</pre>
	</p>
	<h3>Model</h3>
	<p>
		Методы и свойства класса <q>Model</q>:
		
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