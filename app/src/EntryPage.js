import React from "react";
import withAPI from "./components/withAPI";

class EntryPage extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      entry: null,
      saving: false
    };
    this.submit = this.submit.bind(this);
  }

  async componentDidMount() {
    const id = this.props.match.params.id;
    const entry = (await this.props.api.entry(id)).entry;
    this.setState({ entry });
  }

  async submit(e) {
    e.preventDefault();
    const f = e.target;
    const q = f.querySelector("[name=q]").value;
    const a = f.querySelector("[name=a]").value;
    this.setState({ saving: true });
    const id = this.props.match.params.id;
    await this.props.api.updateEntry(id, { q, a });
    this.setState({ saving: false });
  }

  render() {
    const { entry, saving } = this.state;
    if (!entry) return "Loading...";
    return (
      <form method="post" onSubmit={this.submit}>
        <input name="q" defaultValue={entry.q} required />
        <input name="a" defaultValue={entry.a} required />
        <button disabled={saving}>Save</button>
      </form>
    );
  }
}

export default withAPI(EntryPage);
