import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('nomadsguru/deals', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Display Settings', 'nomadsguru')}>
                        <RangeControl
                            label={__('Deals Per Page', 'nomadsguru')}
                            value={attributes.perPage}
                            onChange={(perPage) => setAttributes({ perPage })}
                            min={1}
                            max={50}
                        />
                        <RangeControl
                            label={__('Columns (Desktop)', 'nomadsguru')}
                            value={attributes.columns}
                            onChange={(columns) => setAttributes({ columns })}
                            min={1}
                            max={4}
                        />
                        <SelectControl
                            label={__('Sort By', 'nomadsguru')}
                            value={attributes.sortBy}
                            options={[
                                { label: __('Newest', 'nomadsguru'), value: 'newest' },
                                { label: __('Top Rated', 'nomadsguru'), value: 'score' },
                                { label: __('Cheapest', 'nomadsguru'), value: 'price' },
                            ]}
                            onChange={(sortBy) => setAttributes({ sortBy })}
                        />
                        <RangeControl
                            label={__('Minimum Score', 'nomadsguru')}
                            value={attributes.minScore}
                            onChange={(minScore) => setAttributes({ minScore })}
                            min={0}
                            max={100}
                        />
                        <ToggleControl
                            label={__('Enable Filters', 'nomadsguru')}
                            checked={attributes.enableFilter}
                            onChange={(enableFilter) => setAttributes({ enableFilter })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <ServerSideRender
                        block="nomadsguru/deals"
                        attributes={attributes}
                    />
                </div>
            </>
        );
    },
    save: () => {
        return null; // Server-side rendered
    },
});
