<h1>REST API</h1>
<p>
	������ ������ ������� � API: <code>/api/{collection_name}/[{item_id|collection_method}/[item_method|collection_parametr]]</code>.<br>
	<br><br>
	<h5>����� GET-��������� ��� ���� �������� (��������������):</h5>
	<dl>
		<dt><q>fields</q></dt>
			<dd>�������� ������������� ��������� ������, ���������� ����� �������. �� ��������� �������� ���� ����� ������.</dd>
		<dt><q>count</q></dt>
			<dd>���������� ��������� � ������</dd>
		<dt><q>offset</q></dt>
			<dd>�������� ����������� �������</dd>
		<dt><q>sort</q></dt>
			<dd>���������� ����������� �������. � ��������� ���������� �������� �������, �� �������� ����������� ����������, �� ������� ������������ ������ '-' � ������ ���������� �� �������� (DESC) � ����������� ����� '-' ��� ���������� �� �������� (ASC).</dd>
	</dl>
	<h2>������� �������(�������� �����) API<h2>
	<p>
		<h4><code>GET: /blog/</code></h4>
		<p></p>
		<h4>POST: /blog/</h4>
		<h4>GET: /blog/{article_id}</h4>
		<h4>PUT: /blog/{article_id}</h4>
		
		<h4>GET: /users/</h4>
		<h4>POST: /users/</h4>
		<h4>GET: /users/{user_id}</h4>
		<h4>PUT: /users/{user_id}</h4>
		<h4>POST: /users/auth</h4>
		<h4>POST: /users/forgot</h4>
		<h4>POST: /users/registration</h4>
		
		<h4>POST: /upload/</h4>
	</p>
	<br><br>
	<h2>Backbone.php</h2>
	����������� ����� API ����������� �� ������ � ������� Backbone.js.<br>
	�������� ����� ����������� � ������� ���� �������: Model ��� �������� ������� ��������� �������� � Collection ��� �������� ������ ���������.<br>
	<!--blockquote cite="http://backbonejs.ru/">
		Backbone.js ������� ��������� ���-����������� � ������� ������� � ���������� �� ����� � ����������������� ���������, ��������� � ������� ������� ������� � ������������� ����������, ������������� � ������������� ���������� �������; � ��������� ��� ��� � ����� ������������ REST-���� JSON API.
	</blockquote-->
	<p>
		������ �������� ������� ������ � ��������� ��� ���������� ��������� �������� �����:<br>
		<pre>
			class Item extends Model {
				protected $_table = "`db_name`.`table_name`";
				
				/** Gun API Methods
				 * param $options - ��� ���������� �� ����������� �����, ������ ��� ������ $_GET
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
				 * param $options - ��� ���������� �� ����������� �����
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
		��������� ������ <q>Item</q> ����� ��������� ������ � ��������:
		
	</p>
	<h3>Collection</h3>
	<p>
		��������� ���������� �������� � ������, ����������� ������ � ������� �������.
		
	</p>
	<p></p>
	<p></p>
	<p></p>
	<p></p>
	<p></p>
</p>