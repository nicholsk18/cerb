<?php
class CardWidget_ChartTimeBlocks extends Extension_CardWidget {
	const ID = 'cerb.card.widget.chart.timeblocks';
	
	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_CardWidget $widget) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function getData(Model_CardWidget $widget, string $context, int $context_id, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$data_query = DevblocksPlatform::importGPC($widget->extension_params['data_query'] ?? null, 'string', null);
		$cache_secs = DevblocksPlatform::importGPC($widget->extension_params['cache_secs'] ?? null, 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$bindings = $dict->getDictionary();
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		if(false === ($results = $data->executeQuery($query, $bindings, $error, $cache_secs)))
			return false;
		
		return $results;
	}	
	function render(Model_CardWidget $widget, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		if(false === ($results = $this->getData($widget, $context, $context_id, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(!$results) {
			echo "(no data)";
			return;
		}
		
		if(0 != strcasecmp('timeblocks', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'timeblocks' format.");
			return;
		}
		
		$tpl->assign('data', json_encode($results['data'] ?? []));
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/widgets/_common/chart/timeblocks/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/chart/timeblocks/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $widget) {
		return false;
	}
}
