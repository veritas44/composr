<div class="float-surrounder">
	<div class="confluence-left">
		{$SET,confluence_menu,{$?,{$CONFIG_OPTION,collapse_user_zones},,site}:docs:{ROOT_ID}}

		{$BLOCK,block=menu,param={$GET,confluence_menu},type=popup}
	</div>

	<div class="confluence-main">
		<div class="float-surrounder">
			{HTML}
		</div>
	</div>
</div>
