import React from "react";

function Fail(props) {
  const { question, answer } = props;
  return (
    <article className="fail-card">
      <h3>{question.q}</h3>
      {question.wikiURL && (
        <small>
          <a href={question.wikiURL}>wiki</a>
        </small>
      )}
      <p>
        <a href={`/entries/${question.id}`}>{question.a}</a>{" "}
        <span className="strike">{answer}</span>
      </p>
    </article>
  );
}

function Results(props) {
  const { stats, ok, fail, dict_id: dictID } = props.data;
  return (
    <React.Fragment>
      <section className="test-stats">
        <p>
          {Math.round(
            (stats["right"] / (stats["right"] + stats["wrong"])) * 100
          )}{" "}
          %
        </p>
      </section>

      {fail.map((r, i) => (
        <Fail question={r.question} answer={r.answer} key={i} />
      ))}

      <table>
        <tr>
          <th>Q</th>
          <th>A</th>
          <th />
        </tr>
        {ok.map(r => (
          <tr key={r.question.id}>
            <td>{r["question"]["q"]}</td>
            <td>{r["question"]["a"]}</td>
            <td>ok</td>
          </tr>
        ))}
      </table>

      <nav>
        <a className="btn" href={`/${dictID}/test`}>
          New test
        </a>
        <a className="btn" href="/">
          Home
        </a>
      </nav>
    </React.Fragment>
  );
}

export default Results;
