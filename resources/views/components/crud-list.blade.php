<script>
    document.addEventListener('DOMContentLoaded', () => {
        class CrudListComponent extends ClassApiKitCrud {
            getItemTemplate(item) {
                return (item) => litHtml`@if($itemElement) <{{ $itemElement }} .item="${item}"></{{ $itemElement }}> @elseif(count($displayProperties) === 0) Set $itemElement component or $displayProperties array to show item content @else @foreach($displayProperties as $property) ${ item.{{ $property }} } @if(!$loop->last) - @endif @endforeach @endif`;
            }
        }
        customElements.define('crud-list-component', CrudListComponent);
    });
</script>

<crud-list-component
    endpoint="{{ $endpoint }}"
    config="{{ json_encode($config) }}"
></crud-list-component>