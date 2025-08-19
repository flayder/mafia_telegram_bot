

<a class="nav-link {{ request()->routeIs('botuserList') ? 'active' : '' }}" href="{{ route('botuserList') }}">Пользователи</a>
<a class="nav-link {{ request()->routeIs('gameroleList') ? 'active' : '' }}" href="{{ route('gameroleList') }}">Игровые роли</a>
<a class="nav-link {{ request()->routeIs('currencyList') ? 'active' : '' }}" href="{{ route('currencyList') }}">Валюты</a>
<a class="nav-link {{ request()->routeIs('botgroupList') ? 'active' : '' }}" href="{{ route('botgroupList') }}">Подключенные чаты</a>
<a class="nav-link {{ request()->routeIs('sendcurhistoryList') ? 'active' : '' }}" href="{{ route('sendcurhistoryList') }}">История отправки валют</a>
<a class="nav-link {{ request()->routeIs('chatmemberList') ? 'active' : '' }}" href="{{ route('chatmemberList') }}">Участники чатов</a>
<a class="nav-link {{ request()->routeIs('roletypeList') ? 'active' : '' }}" href="{{ route('roletypeList') }}">Типы ролей</a>
<a class="nav-link {{ request()->routeIs('gamerolesorderList') ? 'active' : '' }}" href="{{ route('gamerolesorderList') }}">Порядок добавления ролей</a>
<a class="nav-link {{ request()->routeIs('taskList') ? 'active' : '' }}" href="{{ route('taskList') }}">Задачи</a>
<a class="nav-link {{ request()->routeIs('settingList') ? 'active' : '' }}" href="{{ route('settingList') }}">Настройки</a>
<a class="nav-link {{ request()->routeIs('voitingList') ? 'active' : '' }}" href="{{ route('voitingList') }}">Голосование</a>
<a class="nav-link {{ request()->routeIs('voteList') ? 'active' : '' }}" href="{{ route('voteList') }}">Голоса</a>
<a class="nav-link {{ request()->routeIs('yesnovoteList') ? 'active' : '' }}" href="{{ route('yesnovoteList') }}">ДА-НЕТ-голоса</a>
<a class="nav-link {{ request()->routeIs('rolesneedfromsaveList') ? 'active' : '' }}" href="{{ route('rolesneedfromsaveList') }}">От кого спасать</a>
<a class="nav-link {{ request()->routeIs('sleepkillroleList') ? 'active' : '' }}" href="{{ route('sleepkillroleList') }}">Кого убьет сон</a>
<a class="nav-link {{ request()->routeIs('bafList') ? 'active' : '' }}" href="{{ route('bafList') }}">Баффы</a>
<a class="nav-link {{ request()->routeIs('achievementList') ? 'active' : '' }}" href="{{ route('achievementList') }}">Достижения</a>
<a class="nav-link {{ request()->routeIs('productList') ? 'active' : '' }}" href="{{ route('productList') }}">Товары</a>
<a class="nav-link {{ request()->routeIs('warningtypeList') ? 'active' : '' }}" href="{{ route('warningtypeList') }}">Типы предупреждений</a>
<a class="nav-link {{ request()->routeIs('warningwordList') ? 'active' : '' }}" href="{{ route('warningwordList') }}">Запрещенные слова</a>
<a class="nav-link {{ request()->routeIs('roleactionList') ? 'active' : '' }}" href="{{ route('roleactionList') }}">Действия в ролях</a>
<a class="nav-link {{ request()->routeIs('buyroleList') ? 'active' : '' }}" href="{{ route('buyroleList') }}">Роли на продажу</a>
<a class="nav-link {{ request()->routeIs('offerList') ? 'active' : '' }}" href="{{ route('offerList') }}">Офферы</a>
<a class="nav-link {{ request()->routeIs('currencyrateList') ? 'active' : '' }}" href="{{ route('currencyrateList') }}">Курс валют</a>
<a class="nav-link {{ request()->routeIs('grouptarifList') ? 'active' : '' }}" href="{{ route('grouptarifList') }}">Тарифы групп</a>
<a class="nav-link {{ request()->routeIs('rewardhistoryList') ? 'active' : '' }}" href="{{ route('rewardhistoryList') }}">История наград</a>
<a class="nav-link {{ request()->routeIs('withdrawalList') ? 'active' : '' }}" href="{{ route('withdrawalList') }}">Вывод средств</a>
<a class="nav-link {{ request()->routeIs('newsletterList') ? 'active' : '' }}" href="{{ route('newsletterList') }}">Рассылки</a>
<a class="nav-link {{ request()->routeIs('newslettertypeList') ? 'active' : '' }}" href="{{ route('newslettertypeList') }}">Типы рассылок</a>
<a class="nav-link {{ request()->routeIs('roulettesprizeList') ? 'active' : '' }}" href="{{ route('roulettesprizeList') }}">Призы в рулетке</a>