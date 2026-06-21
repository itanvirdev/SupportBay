<!-- I'd create the folder but wait on the enum.

Today we only have:

Private Ticket
Public Ticket

Once we implement public sharing, we can add:

enum VisibilityType: string
{
    case PRIVATE = 'private';
    case PUBLIC = 'public';
}

There's no need until we actually use it. -->