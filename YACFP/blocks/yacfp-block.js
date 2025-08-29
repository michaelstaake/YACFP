(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var useBlockProps = wp.blockEditor.useBlockProps;

    registerBlockType('yacfp/contact-form', {
        apiVersion: 2,
        title: __('YACFP Contact Form', 'yacfp'),
        description: __('Inserts a customizable contact form from Yet Another Contact Form Plugin.', 'yacfp'),
        category: 'widgets',
        icon: {
            src: wp.element.createElement('svg', {
                xmlns: 'http://www.w3.org/2000/svg',
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                strokeWidth: '2'
            }, [
                wp.element.createElement('path', { d: 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z' }),
                wp.element.createElement('polyline', { points: '22,6 12,13 2,6' })
            ])
        },
        supports: {
            html: false
        },
        edit: function() {
            var blockProps = useBlockProps();
            return wp.element.createElement(
                'div',
                blockProps,
                wp.element.createElement('p', null, __('YACFP Contact Form Placeholder', 'yacfp'))
            );
        },
        save: function() {
            return '[yacfp]';
        }
    });
})(window.wp);